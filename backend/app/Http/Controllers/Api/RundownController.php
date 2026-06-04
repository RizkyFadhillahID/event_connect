<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRundown;
use App\Models\RundownLog;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RundownController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // Role helpers
    // ──────────────────────────────────────────────────────────────────────

    /** Ensure the user is a member of the event (or a superadmin / project_manager) */
    private function canAccessEvent($user, int $eventId): bool
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return true;
        }
        return DB::table('event_personnel')
            ->where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function getNormalizedEventRole($personnel): string
    {
        if (!$personnel || empty($personnel->role_in_event)) {
            return '';
        }
        $role = strtolower(str_replace([' ', '_', '-'], '', $personnel->role_in_event));
        $translations = [
            'koordinatorrundown' => 'rundowncoordinator',
            'picrundown' => 'rundownpic',
            'pjrundown' => 'rundownpic',
            'stafrundown' => 'rundownpic',
            'perencanaacara' => 'eventplanner',
            'koordinatoracara' => 'eventcoordinator',
            'timteknis' => 'technicalteam',
            'panitiateknis' => 'technicalteam',
            'timtalent' => 'talentteam',
            'logistik' => 'logisticsteam',
            'timlogistik' => 'logisticsteam',
        ];
        return $translations[$role] ?? $role;
    }

    private function canManageRundown($user, int $eventId): bool
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return true;
        }

        $personnel = DB::table('event_personnel')
            ->where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->first();

        if (!$personnel) {
            return false;
        }

        $roleInEvent = $this->getNormalizedEventRole($personnel);

        return in_array($roleInEvent, [
            'rundowncoordinator', 'rundownpic',
            'eventplanner', 'eventcoordinator',
            'projectmanager'
        ]);
    }

    /**
     * Returns true when this user is allowed to change the status of a rundown item.
     * - PIC of the item → always allowed (regardless of role)
     * - Global Superadmin / Project Manager → always allowed
     * - Event-specific manager roles → allowed for any category
     * - Event-specific technical/talent roles → allowed only for respective categories
     */
    private function canUpdateStatus($user, EventRundown $rundown): bool
    {
        if ($rundown->pic_id === $user->id) {
            return true;
        }
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return true;
        }

        $personnel = DB::table('event_personnel')
            ->where('event_id', $rundown->event_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$personnel) {
            return false;
        }

        $roleInEvent = $this->getNormalizedEventRole($personnel);

        // Roles that can update status of any category
        if (in_array($roleInEvent, [
            'rundowncoordinator', 'rundownpic',
            'eventplanner', 'eventcoordinator',
            'operationsteam', 'operationshead',
            'projectmanager'
        ])) {
            return true;
        }

        // Roles mapped to specific categories
        if (in_array($roleInEvent, ['technicalteam', 'technicallead', 'technicalpic'])) {
            return $rundown->category === 'technical';
        }

        if (in_array($roleInEvent, ['talentcoordinator', 'talenthandler', 'talentpic'])) {
            return $rundown->category === 'talent';
        }

        return false;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CRUD
    // ──────────────────────────────────────────────────────────────────────

    /**
     * GET /events/{event}/rundowns
     * Returns rundowns for the event, optionally filtered by date / category / status.
     * Also returns the list of available dates (distinct event_date values).
     */
    public function index(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $event->id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $query = EventRundown::with(['pic:id,name,role'])
            ->forEvent($event->id)
            ->orderBy('event_date')
            ->orderBy('order_number')
            ->orderBy('start_time');

        if ($request->filled('event_date')) {
            $query->forDate($request->event_date);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $rundowns = $query->get()->map(function ($item) {
            $item->append('has_unfinished_dependencies');
            return $item;
        });

        // Distinct dates for the tab-switcher on the frontend
        $dates = EventRundown::forEvent($event->id)
            ->orderBy('event_date')
            ->distinct()
            ->pluck('event_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'));

        return response()->json([
            'data'  => $rundowns,
            'dates' => $dates,
        ]);
    }

    /**
     * POST /events/{event}/rundowns
     */
    public function store(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $event->id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if (!$this->canManageRundown($user, $event->id)) {
            return response()->json(['message' => 'Hanya rundown coordinator, event planner, atau manager yang dapat membuat rundown.'], 403);
        }

        $validated = $request->validate([
            'event_date'       => 'required|date',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'category'         => 'nullable|string|max:100',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'pic_id'           => 'nullable|exists:users,id',
            'location_note'    => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'order_number'     => 'nullable|integer|min:0',
            'dependency_task_ids' => 'nullable|array',
            'dependency_task_ids.*' => 'exists:tasks,id',
        ]);

        // Validate PIC belongs to event personnel
        if (!empty($validated['pic_id'])) {
            $isPersonnel = DB::table('event_personnel')
                ->where('event_id', $event->id)
                ->where('user_id', $validated['pic_id'])
                ->exists();
            $isMgr = in_array(
                optional(\App\Models\User::find($validated['pic_id']))->role,
                ['superadmin', 'project_manager']
            );
            if (!$isPersonnel && !$isMgr) {
                return response()->json(['message' => 'PIC harus terdaftar sebagai personel event.'], 422);
            }
        }

        $rundown = EventRundown::create([
            ...$validated,
            'event_id'   => $event->id,
            'created_by' => $user->id,
            'status'     => 'pending',
        ]);

        // Attach dependency tasks
        if (!empty($validated['dependency_task_ids'])) {
            $rundown->dependencyTasks()->sync($validated['dependency_task_ids']);
        }

        RundownLog::create([
            'rundown_id' => $rundown->id,
            'user_id'    => $user->id,
            'action'     => 'created',
            'new_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Rundown item berhasil dibuat.',
            'data'    => $rundown->load(['pic:id,name,role', 'dependencyTasks:id,title,status']),
        ], 201);
    }

    /**
     * GET /rundowns/{rundown}
     */
    public function show(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $rundown->load([
            'event:id,name,start_date,end_date',
            'pic:id,name,role',
            'creator:id,name,role',
            'dependencyTasks:id,title,status,priority,due_date',
            'logs.user:id,name',
        ]);

        $rundown->append('has_unfinished_dependencies');

        return response()->json(['data' => $rundown]);
    }

    /**
     * PUT /rundowns/{rundown}
     */
    public function update(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if (!$this->canManageRundown($user, $rundown->event_id)) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengedit rundown.'], 403);
        }

        $validated = $request->validate([
            'event_date'       => 'sometimes|date',
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'category'         => 'nullable|string|max:100',
            'start_time'       => 'sometimes|date_format:H:i',
            'end_time'         => 'sometimes|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:1',
            'pic_id'           => 'nullable|exists:users,id',
            'location_note'    => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'order_number'     => 'nullable|integer|min:0',
            'dependency_task_ids' => 'nullable|array',
            'dependency_task_ids.*' => 'exists:tasks,id',
        ]);

        // Validate PIC belongs to event personnel
        if (!empty($validated['pic_id'])) {
            $isPersonnel = DB::table('event_personnel')
                ->where('event_id', $rundown->event_id)
                ->where('user_id', $validated['pic_id'])
                ->exists();
            $isMgr = in_array(
                optional(\App\Models\User::find($validated['pic_id']))->role,
                ['superadmin', 'project_manager']
            );
            if (!$isPersonnel && !$isMgr) {
                return response()->json(['message' => 'PIC harus terdaftar sebagai personel event.'], 422);
            }
        }

        $rundown->update($validated);

        if (array_key_exists('dependency_task_ids', $validated)) {
            $rundown->dependencyTasks()->sync($validated['dependency_task_ids'] ?? []);
        }

        return response()->json([
            'message' => 'Rundown item berhasil diperbarui.',
            'data'    => $rundown->load(['pic:id,name,role', 'dependencyTasks:id,title,status']),
        ]);
    }

    /**
     * PATCH /rundowns/{rundown}/status
     * Handles status changes with audit log and delay tracking.
     */
    public function updateStatus(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if (!$this->canUpdateStatus($user, $rundown)) {
            return response()->json(['message' => 'Anda tidak memiliki izin untuk mengubah status item ini.'], 403);
        }

        $validated = $request->validate([
            'status'       => 'required|in:pending,ready,live,delayed,completed',
            'delay_reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $rundown->status;
        $newStatus = $validated['status'];

        $changes = ['status' => $newStatus];

        // Auto-set started_at when going Live
        if ($newStatus === 'live' && !$rundown->started_at) {
            $changes['started_at'] = now();
        }
        // Auto-set ended_at when completed
        if ($newStatus === 'completed' && !$rundown->ended_at) {
            $changes['ended_at'] = now();
            if ($rundown->started_at) {
                $changes['delay_minutes'] = now()->diffInMinutes($rundown->started_at) - ($rundown->duration_minutes ?? 0);
                $changes['delay_minutes'] = max(0, $changes['delay_minutes']);
            }
        }
        // Track delay explicitly
        if ($newStatus === 'delayed' && isset($validated['delay_reason'])) {
            $changes['delay_minutes'] = $request->input('delay_minutes', $rundown->delay_minutes);
        }

        $rundown->update($changes);

        RundownLog::create([
            'rundown_id'   => $rundown->id,
            'user_id'      => $user->id,
            'action'       => 'status_changed',
            'old_status'   => $oldStatus,
            'new_status'   => $newStatus,
            'delay_reason' => $validated['delay_reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Status berhasil diperbarui.',
            'data'    => $rundown,
        ]);
    }

    /**
     * DELETE /rundowns/{rundown}
     */
    public function destroy(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        $allowed = false;
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            $allowed = true;
        } else {
            $personnel = DB::table('event_personnel')
                ->where('event_id', $rundown->event_id)
                ->where('user_id', $user->id)
                ->first();
            if ($personnel) {
                $roleInEvent = $this->getNormalizedEventRole($personnel);
                $allowed = in_array($roleInEvent, ['rundowncoordinator', 'rundownpic', 'projectmanager', 'eventplanner', 'eventcoordinator']);
            }
        }

        if (!$allowed) {
            return response()->json(['message' => 'Hanya rundown coordinator atau manager yang dapat menghapus rundown.'], 403);
        }

        $rundown->delete();

        return response()->json(['message' => 'Rundown item berhasil dihapus.']);
    }

    /**
     * GET /rundowns/{rundown}/logs
     */
    public function logs(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $logs = $rundown->logs()->with('user:id,name,role')->get();

        return response()->json(['data' => $logs]);
    }

    /**
     * POST /rundowns/{rundown}/dependencies
     * Add task dependency (informational / warning only).
     */
    public function addDependency(Request $request, EventRundown $rundown)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if (!$this->canManageRundown($user, $rundown->event_id)) {
            return response()->json(['message' => 'Tidak memiliki izin.'], 403);
        }

        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
        ]);

        // Ensure the task belongs to the same event
        $task = Task::find($validated['task_id']);
        if ($task->event_id !== $rundown->event_id) {
            return response()->json(['message' => 'Task harus berada dalam event yang sama.'], 422);
        }

        $rundown->dependencyTasks()->syncWithoutDetaching([$validated['task_id']]);

        return response()->json([
            'message' => 'Dependency berhasil ditambahkan.',
            'data'    => $rundown->dependencyTasks()->get(['tasks.id', 'title', 'status']),
        ]);
    }

    /**
     * DELETE /rundowns/{rundown}/dependencies/{task}
     */
    public function removeDependency(Request $request, EventRundown $rundown, Task $task)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $rundown->event_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if (!$this->canManageRundown($user, $rundown->event_id)) {
            return response()->json(['message' => 'Tidak memiliki izin.'], 403);
        }

        $rundown->dependencyTasks()->detach($task->id);

        return response()->json([
            'message' => 'Dependency berhasil dihapus.',
            'data'    => $rundown->dependencyTasks()->get(['tasks.id', 'title', 'status']),
        ]);
    }

    /**
     * GET /events/{event}/rundown-dates
     * Returns list of distinct event_dates that have rundown items.
     */
    public function eventDates(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $event->id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $dates = EventRundown::forEvent($event->id)
            ->orderBy('event_date')
            ->distinct()
            ->pluck('event_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'));

        return response()->json(['data' => $dates]);
    }

    /**
     * GET /events/{event}/rundown-stats
     * Returns summary stats for the event rundown.
     */
    public function eventStats(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$this->canAccessEvent($user, $event->id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $counts = EventRundown::forEvent($event->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $counts->sum();

        return response()->json([
            'data' => [
                'total'     => $total,
                'pending'   => $counts->get('pending', 0),
                'ready'     => $counts->get('ready', 0),
                'live'      => $counts->get('live', 0),
                'delayed'   => $counts->get('delayed', 0),
                'completed' => $counts->get('completed', 0),
                'progress'  => $total > 0 ? (int) round($counts->get('completed', 0) / $total * 100) : 0,
            ],
        ]);
    }
}
