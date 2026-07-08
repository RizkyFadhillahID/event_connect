<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventReportController extends Controller
{
    /**
     * Get the evaluation report or a dynamic live preview of the metrics.
     */
    public function show(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        // Fetch finalized report
        $report = $event->report()->with('user:id,name,role')->first();

        if ($report) {
            return response()->json([
                'is_finalized' => true,
                'report' => $report
            ]);
        }

        // If not finalized, compile live preview (only for PM & Admin who can finalize)
        $this->authorizeManagerAccess($request->user());

        $preview = $this->compileLiveSnapshot($event);

        return response()->json([
            'is_finalized' => false,
            'preview' => $preview
        ]);
    }

    /**
     * Create or update a finalized evaluation report.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorizeManagerAccess($request->user());

        $data = $request->validate([
            'evaluation_notes' => 'required|string',
            'recommendations' => 'required|string',
        ]);

        $snapshot = $this->compileLiveSnapshot($event);

        $report = DB::transaction(function () use ($event, $request, $data, $snapshot) {
            return EventReport::updateOrCreate(
                ['event_id' => $event->id],
                [
                    'user_id' => $request->user()->id,
                    'evaluation_notes' => $data['evaluation_notes'],
                    'recommendations' => $data['recommendations'],
                    'budget_snapshot' => $snapshot['budget'],
                    'task_snapshot' => $snapshot['task'],
                    'logistic_snapshot' => $snapshot['logistic'],
                    'finalized_at' => Carbon::now()
                ]
            );
        });

        return response()->json([
            'message' => 'Laporan evaluasi berhasil diterbitkan.',
            'report' => $report->load('user:id,name,role')
        ], 201);
    }

    /**
     * Reset / Delete a finalized report.
     */
    public function destroy(Request $request, Event $event)
    {
        $this->authorizeManagerAccess($request->user());

        $report = $event->report()->first();
        if (!$report) {
            return response()->json(['message' => 'Laporan evaluasi tidak ditemukan.'], 404);
        }

        $report->delete();

        return response()->json(['message' => 'Laporan evaluasi berhasil dihapus/direset.']);
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Compile real-time snapshots of event modules.
     */
    private function compileLiveSnapshot(Event $event): array
    {
        // 1. Budget Snapshot
        $allocations = $event->budgetAllocations()->get();
        $expenses = $event->expenses()->get();

        $categoriesBreakdown = [];
        $defaultCategories = ['Konsumsi', 'Sound & Lighting', 'Venue / Akomodasi', 'Dekorasi & Stage', 'Talent & MC', 'Publikasi & Dok', 'Logistik & Ops', 'Lain-lain'];
        
        $categories = collect($defaultCategories)
            ->merge($allocations->pluck('category'))
            ->merge($expenses->pluck('category'))
            ->unique()
            ->values();
        
        foreach ($categories as $cat) {
            $allocated = $allocations->where('category', $cat)->sum('allocated_amount');
            $spent = $expenses->where('category', $cat)->sum('amount');
            $categoriesBreakdown[] = [
                'category' => $cat,
                'allocated' => (float)$allocated,
                'spent' => (float)$spent
            ];
        }

        $totalAllocated = $allocations->sum('allocated_amount');
        $totalSpent = $expenses->sum('amount');

        $budgetSnapshot = [
            'event_budget' => (float)$event->budget,
            'total_allocated' => (float)$totalAllocated,
            'total_spent' => (float)$totalSpent,
            'remaining_budget' => (float)($event->budget - $totalSpent),
            'categories' => $categoriesBreakdown
        ];

        // 2. Task Snapshot
        $tasks = $event->tasks()->get();
        $totalTasks = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        
        $overdue = 0;
        foreach ($tasks as $t) {
            if ($t->status !== 'completed' && $t->due_date) {
                $dueDate = Carbon::parse($t->due_date);
                if ($dueDate->isPast()) {
                    $overdue++;
                }
            }
        }

        $taskSnapshot = [
            'total' => $totalTasks,
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'review' => $tasks->where('status', 'review')->count(),
            'completed' => $completed,
            'cancelled' => $tasks->where('status', 'cancelled')->count(),
            'progress' => $totalTasks > 0 ? (int)round(($completed / $totalTasks) * 100) : 0,
            'overdue' => $overdue
        ];

        // 3. Logistic Snapshot
        $logistics = $event->logistics()->with('inventory')->get();
        $totalLoans = $logistics->count();
        $returned = $logistics->whereNotNull('returned_at')->count();
        
        $details = [];
        foreach ($logistics as $logi) {
            $details[] = [
                'item_name' => $logi->inventory?->item_name || 'Alat',
                'quantity' => $logi->quantity,
                'ownership' => $logi->inventory?->ownership || 'owned',
                'borrowed_at' => $logi->borrowed_at,
                'returned_at' => $logi->returned_at,
                'return_status' => $logi->return_status,
                'rent_cost' => (float)$logi->rent_cost,
                'notes' => $logi->notes
            ];
        }

        $logisticSnapshot = [
            'total_items_borrowed' => $logistics->sum('quantity'),
            'total_allocations_count' => $totalLoans,
            'items_in_field' => $logistics->whereNull('returned_at')->sum('quantity'),
            'items_returned' => $logistics->whereNotNull('returned_at')->sum('quantity'),
            'returned_complete' => $logistics->where('return_status', 'complete')->sum('quantity'),
            'returned_incomplete' => $logistics->where('return_status', 'incomplete')->sum('quantity'),
            'returned_damaged' => $logistics->where('return_status', 'damaged')->sum('quantity'),
            'total_rent_costs' => (float)$logistics->sum('rent_cost'),
            'details' => $details
        ];

        return [
            'budget' => $budgetSnapshot,
            'task' => $taskSnapshot,
            'logistic' => $logisticSnapshot
        ];
    }

    /**
     * RBAC check for PM or Admin
     */
    private function authorizeManagerAccess($user): void
    {
        abort_unless(
            in_array($user->role, ['superadmin', 'project_manager']),
            403,
            'Hanya Project Manager atau Super Admin yang memiliki hak mengelola laporan evaluasi.'
        );
    }

    /**
     * RBAC check for Event Personnel
     */
    private function authorizeEventAccess($user, Event $event): void
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return;
        }

        $isMember = DB::table('event_personnel')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'Anda tidak memiliki akses ke laporan evaluasi event ini.');
    }

    /**
     * Render the formal print evaluation report view.
     */
    public function printReport(Request $request, Event $event)
    {
        $token = $request->query('token');
        if (!$token) {
            abort(401, 'Token autentikasi tidak ditemukan.');
        }

        // Authenticate user via Personal Access Token
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$personalAccessToken) {
            abort(401, 'Token autentikasi tidak valid atau telah kedaluwarsa.');
        }

        $user = $personalAccessToken->tokenable;
        if (!$user) {
            abort(401, 'Pengguna tidak valid.');
        }

        // Check if user has access to this event
        $this->authorizeEventAccess($user, $event);

        // Fetch finalized report
        $report = $event->report()->with('user:id,name,role')->first();
        if (!$report) {
            abort(404, 'Laporan evaluasi belum diterbitkan oleh Project Manager untuk event ini.');
        }

        // Load organization details
        $organization = \App\Models\Organization::findOrFail($user->organization_id);

        // Run detailed queries for the report (with relations)
        $allocations = $event->budgetAllocations()->orderBy('category')->get();
        $expenses = $event->expenses()->with('user:id,name')->orderBy('spent_at')->get();
        $tasks = $event->tasks()->with('assignee:id,name')->orderBy('due_date')->get();
        $logistics = $event->logistics()->with(['inventory', 'user:id,name'])->orderBy('borrowed_at')->get();
        $guests = $event->guests()->orderBy('name')->get();

        $publishDate = $report->finalized_at ? Carbon::parse($report->finalized_at)->format('d M Y') : Carbon::now()->format('d M Y');

        return view('report_print', [
            'event' => $event,
            'organization' => $organization,
            'report' => $report,
            'allocations' => $allocations,
            'expenses' => $expenses,
            'tasks' => $tasks,
            'logistics' => $logistics,
            'guests' => $guests,
            'publishDate' => $publishDate
        ]);
    }
}
