<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user();
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $events = Event::with(['creator:id,name', 'personnel:id,name,role'])
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            // Staff & other roles only see events they are registered in
            ->when(
                !in_array($user->role, ['superadmin', 'project_manager']),
                fn($q) => $q->whereHas('personnel', fn($inner) => $inner->where('users.id', $user->id))
            )
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'status' => 'required|in:draft,active,ongoing,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'expected_participants' => 'nullable|integer|min:0',
            'personnel' => 'nullable|array',
            'personnel.*.user_id' => 'required|exists:users,id',
            'personnel.*.role_in_event' => 'nullable|string|max:100',
            'personnel.*.notes' => 'nullable|string',
        ]);

        $this->normalizeTimeFields($data);

        $data['created_by'] = auth()->id();
        $personnel = $data['personnel'] ?? [];
        unset($data['personnel']);

        $event = DB::transaction(function () use ($data, $personnel) {
            $event = Event::create($data);

            if (!empty($personnel)) {
                $syncData = [];
                foreach ($personnel as $p) {
                    $syncData[$p['user_id']] = [
                        'role_in_event' => $p['role_in_event'] ?? null,
                        'notes' => $p['notes'] ?? null,
                    ];
                }
                $event->personnel()->sync($syncData);
            }

            return $event;
        });

        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']), 201);
    }

    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'project_manager'])) {
            $isMember = $event->personnel()->where('users.id', $user->id)->exists();
            abort_unless($isMember, 403, 'Anda tidak memiliki akses ke event ini.');
        }

        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']));
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'status' => 'sometimes|in:draft,active,ongoing,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'expected_participants' => 'nullable|integer|min:0',
            'personnel' => 'nullable|array',
            'personnel.*.user_id' => 'required|exists:users,id',
            'personnel.*.role_in_event' => 'nullable|string|max:100',
            'personnel.*.notes' => 'nullable|string',
        ]);

        $this->normalizeTimeFields($data);

        if (array_key_exists('end_date', $data)) {
            $startDate = $data['start_date'] ?? optional($event->start_date)->format('Y-m-d');
            if ($startDate && $data['end_date'] < $startDate) {
                throw ValidationException::withMessages([
                    'end_date' => ['The end date field must be a date after or equal to start date.'],
                ]);
            }
        }

        $personnel = $data['personnel'] ?? null;
        unset($data['personnel']);

        DB::transaction(function () use ($event, $data, $personnel) {
            $event->update($data);

            if ($personnel !== null) {
                $syncData = [];
                foreach ($personnel as $p) {
                    $syncData[$p['user_id']] = [
                        'role_in_event' => $p['role_in_event'] ?? null,
                        'notes' => $p['notes'] ?? null,
                    ];
                }
                $event->personnel()->sync($syncData);
            }
        });

        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']));
    }

    public function destroy(Event $event)
    {
        try {
            $event->delete();
            return response()->json(['message' => 'Event berhasil dihapus.']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Event tidak dapat dihapus. Periksa relasi data terkait terlebih dahulu.',
            ], 409);
        }
    }

    public function allUsers()
    {
        $users = User::select('id', 'name', 'role', 'status')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        return response()->json($users);
    }

    private function normalizeTimeFields(array &$data): void
    {
        foreach (['start_time', 'end_time'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            // Accept both HH:mm and HH:mm:ss from UI/DB, persist as HH:mm.
            $data[$field] = substr((string) $data[$field], 0, 5);
        }
    }
}
