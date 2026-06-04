<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Presence channel for event group chat.
 * Returns user info so members can see who is online.
 */
Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    // Check if the event exists and belongs to the user's organization
    $event = \App\Models\Event::withoutGlobalScopes()
        ->where('id', $eventId)
        ->where('organization_id', $user->organization_id)
        ->first();

    if (!$event) {
        return false;
    }

    if (in_array($user->role, ['superadmin', 'project_manager'])) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
    }

    $isMember = DB::table('event_personnel')
        ->where('event_id', $eventId)
        ->where('user_id', $user->id)
        ->exists();

    if ($isMember) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
    }

    return false;
});
