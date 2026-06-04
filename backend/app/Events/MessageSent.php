<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->load('user');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PresenceChannel('event.' . $this->message->event_id),
        ];

        $event = Event::withoutGlobalScopes()->find($this->message->event_id);
        if ($event) {
            $userIds = DB::table('event_personnel')
                ->where('event_id', $event->id)
                ->pluck('user_id')
                ->toArray();

            if ($event->created_by) {
                $userIds[] = $event->created_by;
            }

            $userIds = array_unique($userIds);

            foreach ($userIds as $userId) {
                $channels[] = new PrivateChannel('App.Models.User.' . $userId);
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'event_id'   => $this->message->event_id,
            'message'    => $this->message->message,
            'file_url'   => $this->message->file_path ? asset('storage/' . $this->message->file_path) : null,
            'file_name'  => $this->message->file_name,
            'file_type'  => $this->message->file_type,
            'created_at' => $this->message->created_at->toISOString(),
            'user'       => [
                'id'   => $this->message->user->id,
                'name' => $this->message->user->name,
                'role' => $this->message->user->role,
            ],
        ];
    }
}
