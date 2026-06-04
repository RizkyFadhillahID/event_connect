<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Return paginated chat history for the event.
     * Only event members (or superadmin/PM) may access.
     */
    public function index(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        // Single query: get 50 latest by id desc, then reverse to chronological order
        $messages = ChatMessage::with(['user:id,name,role'])
            ->where('event_id', $event->id)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => $this->formatMessage($m));

        return response()->json($messages);
    }

    /**
     * Store a new chat message and broadcast it.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        $request->validate([
            'message' => ['required_without:file', 'nullable', 'string', 'max:1000'],
            'file'    => ['nullable', 'file', 'max:10240'], // Max 10MB
        ]);

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $user = $request->user();
            if ($user->organization && !$user->organization->hasFeature('chat_attachments')) {
                return response()->json([
                    'message' => 'Unggah berkas atau foto di chat tidak didukung pada paket Free. Silakan hubungi admin untuk upgrade ke paket Business atau Enterprise.'
                ], 403);
            }

            $file = $request->file('file');
            $filePath = $file->store('chat_attachments', 'public');
            $fileName = $file->getClientOriginalName();
            
            $mime = $file->getMimeType();
            $fileType = str_starts_with($mime, 'image/') ? 'image' : 'file';
        }

        $message = ChatMessage::create([
            'event_id'  => $event->id,
            'user_id'   => $request->user()->id,
            'message'   => $request->message ?? '',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);

        broadcast(new MessageSent($message));

        $message->load('user');

        return response()->json($this->formatMessage($message), 201);
    }

    // ---------------------------------------------------------------

    private function authorizeEventAccess($user, Event $event): void
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return;
        }

        $isMember = DB::table('event_personnel')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'You are not a member of this event.');
    }

    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id'         => $message->id,
            'event_id'   => $message->event_id,
            'message'    => $message->message,
            'file_url'   => $message->file_path ? asset('storage/' . $message->file_path) : null,
            'file_name'  => $message->file_name,
            'file_type'  => $message->file_type,
            'created_at' => $message->created_at->toISOString(),
            'user'       => [
                'id'   => $message->user->id,
                'name' => $message->user->name,
                'role' => $message->user->role,
            ],
        ];
    }
}
