<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * List tasks with rich filtering:
     * - event_id, assigned_to, status, priority, search, my_tasks, page
     * - Personnel can only see tasks of events they are assigned to
     * - PM/Superadmin see all tasks
     */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Task::with(['event:id,name,status', 'assignee:id,name,role', 'creator:id,name', 'subtasks'])
            ->rootTasks()
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->orderBy('order');

        // Scope by accessible events for non-managers
        if (!in_array($user->role, ['superadmin', 'project_manager'])) {
            $accessibleEventIds = DB::table('event_personnel')
                ->where('user_id', $user->id)
                ->pluck('event_id');
            $query->whereIn('event_id', $accessibleEventIds);
        }

        // Filters
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->boolean('my_tasks')) {
            $query->where('assigned_to', $user->id);
        }
        if ($request->boolean('overdue')) {
            $query->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled']);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 20);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Create a task.
     * PM/Superadmin: can create for any event.
     * Event personnel: can create tasks only for events they are assigned to.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'event_id'       => 'required|exists:events,id',
            'assigned_to'    => 'nullable|exists:users,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'priority'       => 'required|in:low,medium,high,urgent',
            'status'         => 'required|in:pending,in_progress,review,completed,cancelled',
            'due_date'       => 'nullable|date',
            'due_time'       => 'nullable|date_format:H:i',
            'category'       => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
            'order'          => 'nullable|integer',
        ]);

        // Check event access for non-managers
        if (!in_array($user->role, ['superadmin', 'project_manager'])) {
            $assigned = DB::table('event_personnel')
                ->where('event_id', $data['event_id'])
                ->where('user_id', $user->id)
                ->exists();
            if (!$assigned) {
                return response()->json(['message' => 'Anda tidak terdaftar sebagai personel event ini.'], 403);
            }
        }

        // Validate assignee is personnel of the event
        if (!empty($data['assigned_to'])) {
            $isPersonnel = DB::table('event_personnel')
                ->where('event_id', $data['event_id'])
                ->where('user_id', $data['assigned_to'])
                ->exists();
            if (!$isPersonnel && !in_array($user->role, ['superadmin', 'project_manager'])) {
                return response()->json(['message' => 'User yang ditugaskan bukan personel event ini.'], 422);
            }
        }

        $data['created_by'] = $user->id;
        $task = Task::create($data);

        return response()->json($task->load(['event:id,name', 'assignee:id,name,role', 'creator:id,name']), 201);
    }

    /**
     * Show a single task with full details: subtasks, comments, event info.
     */
    public function show(Request $request, Task $task)
    {
        $user = $request->user();

        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $task->load([
            'event:id,name,status,start_date,end_date',
            'assignee:id,name,role',
            'creator:id,name,role',
            'parent:id,title,status',
            'subtasks.assignee:id,name',
            'subtasks.creator:id,name',
            'comments.user:id,name,role',
        ]);

        $task->append('is_overdue');
        return response()->json($task);
    }

    /**
     * Full update (PM/Superadmin or creator).
     */
    public function update(Request $request, Task $task)
    {
        $user = $request->user();

        if (!$this->canManageTask($user, $task)) {
            return response()->json(['message' => 'Hanya Project Manager / Superadmin / pembuat task yang dapat mengedit.'], 403);
        }

        $data = $request->validate([
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'nullable|string',
            'event_id'       => 'sometimes|required|exists:events,id',
            'assigned_to'    => 'nullable|exists:users,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'priority'       => 'sometimes|in:low,medium,high,urgent',
            'status'         => 'sometimes|in:pending,in_progress,review,completed,cancelled',
            'due_date'       => 'nullable|date',
            'due_time'       => 'nullable|date_format:H:i',
            'category'       => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
            'order'          => 'nullable|integer',
        ]);

        // Auto-set completed_at when marking completed
        if (isset($data['status'])) {
            if ($data['status'] === 'completed' && $task->status !== 'completed') {
                $data['completed_at'] = now();
            } elseif ($data['status'] !== 'completed') {
                $data['completed_at'] = null;
            }
        }

        $task->update($data);
        return response()->json($task->load(['event:id,name', 'assignee:id,name,role', 'creator:id,name']));
    }

    /**
     * Quick status update — accessible by the assigned user too.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $user = $request->user();

        // Assignee can update their own task status
        $isAssignee = $task->assigned_to === $user->id;
        if (!$isAssignee && !$this->canManageTask($user, $task)) {
            return response()->json(['message' => 'Hanya pemilik task atau manager yang dapat mengubah status.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,review,completed,cancelled',
        ]);

        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $task->update($data);

        return response()->json($task->load(['assignee:id,name', 'creator:id,name']));
    }

    /**
     * Delete a task (PM/Superadmin only).
     */
    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'project_manager'])) {
            return response()->json(['message' => 'Hanya Project Manager / Superadmin yang dapat menghapus task.'], 403);
        }
        $task->delete();
        return response()->json(['message' => 'Task berhasil dihapus.']);
    }

    /**
     * Get comments for a task.
     */
    public function comments(Request $request, Task $task)
    {
        $user = $request->user();
        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        return response()->json($task->comments()->with('user:id,name,role')->get());
    }

    /**
     * Add a comment — any user with event access.
     */
    public function addComment(Request $request, Task $task)
    {
        $user = $request->user();
        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);

        return response()->json($comment->load('user:id,name,role'), 201);
    }

    /**
     * My tasks — current user's assigned tasks.
     */
    public function myTasks(Request $request)
    {
        $user  = $request->user();
        $tasks = Task::with(['event:id,name', 'creator:id,name', 'subtasks'])
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(function ($t) {
                $t->append('is_overdue');
                return $t;
            });
        return response()->json($tasks);
    }

    /**
     * Tasks for a specific event (includes stats).
     */
    public function eventTasks(Request $request, Event $event)
    {
        $user = $request->user();

        if (!in_array($user->role, ['superadmin', 'project_manager'])) {
            $isPersonnel = DB::table('event_personnel')
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$isPersonnel) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
        }

        $tasks = Task::with(['assignee:id,name,role', 'creator:id,name', 'subtasks'])
            ->where('event_id', $event->id)
            ->rootTasks()
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->orderBy('due_date')
            ->get()
            ->map(function ($t) {
                $t->append('is_overdue');
                return $t;
            });

        $total     = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();

        $stats = [
            'total'       => $total,
            'pending'     => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'review'      => $tasks->where('status', 'review')->count(),
            'completed'   => $completed,
            'cancelled'   => $tasks->where('status', 'cancelled')->count(),
            'overdue'     => $tasks->filter(fn($t) => $t->is_overdue)->count(),
            'progress'    => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];

        return response()->json(compact('tasks', 'stats'));
    }

    /**
     * Get personnel list for a specific event (for task assignment dropdown).
     */
    public function eventPersonnel(Request $request, Event $event)
    {
        $personnel = DB::table('event_personnel')
            ->join('users', 'event_personnel.user_id', '=', 'users.id')
            ->where('event_personnel.event_id', $event->id)
            ->where('users.status', 'active')
            ->select('users.id', 'users.name', 'users.role', 'event_personnel.role_in_event')
            ->get();

        return response()->json($personnel);
    }

    // --- Helpers ---

    private function canAccessTask($user, Task $task): bool
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return true;
        }
        return DB::table('event_personnel')
            ->where('event_id', $task->event_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function canManageTask($user, Task $task): bool
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return true;
        }
        return $task->created_by === $user->id;
    }
}
