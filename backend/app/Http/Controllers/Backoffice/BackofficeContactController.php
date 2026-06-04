<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class BackofficeContactController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(10);
        return response()->json($messages);
    }

    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        return response()->json($message);
    }

    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json([
            'message' => 'Pesan contact us berhasil dihapus.'
        ]);
    }
}
