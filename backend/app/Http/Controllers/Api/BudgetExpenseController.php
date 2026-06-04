<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBudgetAllocation;
use App\Models\EventExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetExpenseController extends Controller
{
    /**
     * Get budget summary, allocations, and actual expenses list.
     */
    public function index(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        $allocations = $event->budgetAllocations()->get();
        $expenses = $event->expenses()->with('user:id,name,role')->orderBy('spent_at', 'desc')->get();

        $totalAllocated = $allocations->sum('allocated_amount');
        $totalSpent = $expenses->sum('amount');
        $totalBudget = $event->budget ?? 0;
        $remaining = $totalBudget - $totalSpent;

        return response()->json([
            'event_budget' => $totalBudget,
            'total_allocated' => $totalAllocated,
            'total_spent' => $totalSpent,
            'remaining_budget' => $remaining,
            'allocations' => $allocations,
            'expenses' => $expenses,
        ]);
    }

    /**
     * Store or update budget allocation for a category (Manager only).
     */
    public function storeAllocation(Request $request, Event $event)
    {
        $this->authorizeManagerAccess($request->user());

        $data = $request->validate([
            'category' => 'required|string|max:100',
            'allocated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $allocation = EventBudgetAllocation::updateOrCreate(
            ['event_id' => $event->id, 'category' => $data['category']],
            ['allocated_amount' => $data['allocated_amount'], 'notes' => $data['notes'] ?? null]
        );

        return response()->json($allocation, 201);
    }

    /**
     * Delete a budget allocation (Manager only).
     */
    public function destroyAllocation(Request $request, Event $event, EventBudgetAllocation $allocation)
    {
        $this->authorizeManagerAccess($request->user());
        
        abort_unless($allocation->event_id === $event->id, 400, 'Allocation does not belong to this event.');

        $allocation->delete();

        return response()->json(['message' => 'Alokasi anggaran berhasil dihapus.']);
    }

    /**
     * Store a new expense (Any event personnel or manager).
     */
    public function storeExpense(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'payment_method' => 'required|string|max:50',
            'payment_status' => 'required|string|max:50',
            'spent_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $data['event_id'] = $event->id;
        $data['user_id'] = $request->user()->id;

        $expense = EventExpense::create($data);

        return response()->json($expense->load('user:id,name,role'), 201);
    }

    /**
     * Delete an expense (Manager or the creator of the expense).
     */
    public function destroyExpense(Request $request, Event $event, EventExpense $expense)
    {
        abort_unless($expense->event_id === $event->id, 400, 'Expense does not belong to this event.');

        $user = $request->user();
        $isManager = in_array($user->role, ['superadmin', 'project_manager']);
        $isCreator = $expense->user_id === $user->id;

        abort_unless($isManager || $isCreator, 403, 'Anda tidak memiliki hak untuk menghapus transaksi ini.');

        $expense->delete();

        return response()->json(['message' => 'Transaksi pengeluaran berhasil dihapus.']);
    }

    // ------------------------------------------------------------------

    private function authorizeEventAccess($user, Event $event): void
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return;
        }

        $isMember = DB::table('event_personnel')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'Anda tidak memiliki akses ke data anggaran event ini.');
    }

    private function authorizeManagerAccess($user): void
    {
        abort_unless(
            in_array($user->role, ['superadmin', 'project_manager']),
            403,
            'Hanya Project Manager atau Super Admin yang dapat mengatur alokasi anggaran.'
        );
    }
}
