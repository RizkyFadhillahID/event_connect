<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
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
        return [
            new PresenceChannel('event.' . $this->message->event_id),
        ];
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
            'created_at' => $this->message->created_at->toISOString(),
            'user'       => [
                'id'   => $this->message->user->id,
                'name' => $this->message->user->name,
                'role' => $this->message->user->role,
            ],
        ];
    }
}
