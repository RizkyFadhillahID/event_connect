<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::with(['creator:id,name', 'personnel:id,name,role'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('location', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('start_date', 'desc')
            ->paginate(10);

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
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:draft,active,ongoing,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'expected_participants' => 'nullable|integer|min:0',
            'personnel' => 'nullable|array',
            'personnel.*.user_id' => 'required|exists:users,id',
            'personnel.*.role_in_event' => 'nullable|string|max:100',
            'personnel.*.notes' => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $personnel = $data['personnel'] ?? [];
        unset($data['personnel']);

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

        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']), 201);
    }

    public function show(Event $event)
    {
        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']));
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => 'sometimes|in:draft,active,ongoing,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'expected_participants' => 'nullable|integer|min:0',
            'personnel' => 'nullable|array',
            'personnel.*.user_id' => 'required|exists:users,id',
            'personnel.*.role_in_event' => 'nullable|string|max:100',
            'personnel.*.notes' => 'nullable|string',
        ]);

        $personnel = $data['personnel'] ?? null;
        unset($data['personnel']);

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

        return response()->json($event->load(['creator:id,name', 'personnel:id,name,role']));
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Event berhasil dihapus.']);
    }

    public function allUsers()
    {
        $users = User::select('id', 'name', 'role', 'status')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        return response()->json($users);
    }
}
