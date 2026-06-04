<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);
        $guests = $event->guests()->orderBy('name')->get();
        return response()->json($guests);
    }

    public function store(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'category'    => 'required|in:vvip,vip,regular',
            'rsvp_status' => 'required|in:pending,attending,declined',
        ]);

        $data['event_id'] = $event->id;
        $guest = Guest::create($data);

        return response()->json($guest, 201);
    }

    public function update(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEventAccess($request->user(), $event);
        abort_unless($guest->event_id === $event->id, 400, 'Guest tidak terdaftar di event ini.');

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'category'    => 'sometimes|required|in:vvip,vip,regular',
            'rsvp_status' => 'sometimes|required|in:pending,attending,declined',
        ]);

        $guest->update($data);

        return response()->json($guest);
    }

    public function destroy(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEventAccess($request->user(), $event);
        abort_unless($guest->event_id === $event->id, 400, 'Guest tidak terdaftar di event ini.');

        $guest->delete();

        return response()->json(['message' => 'Tamu berhasil dihapus.']);
    }

    public function checkin(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEventAccess($request->user(), $event);
        abort_unless($guest->event_id === $event->id, 400, 'Guest tidak terdaftar di event ini.');

        $status = $request->input('checkin_status', true);
        $guest->checkin_status = $status;
        $guest->checked_in_at = $status ? now() : null;
        $guest->save();

        return response()->json($guest);
    }

    private function authorizeEventAccess($user, Event $event): void
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return;
        }

        $isMember = DB::table('event_personnel')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'Anda tidak memiliki akses ke data tamu event ini.');
    }
}
