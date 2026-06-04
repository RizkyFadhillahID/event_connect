<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;

class BackofficeLandingController extends Controller
{
    public function index()
    {
        $contents = LandingContent::all()->keyBy('key_name');
        
        $formatted = [];
        foreach ($contents as $key => $content) {
            $decoded = json_decode($content->content, true);
            $formatted[$key] = [
                'id' => $content->id,
                'key_name' => $content->key_name,
                'title' => $content->title,
                'content' => json_last_error() === JSON_ERROR_NONE ? $decoded : $content->content
            ];
        }

        return response()->json($formatted);
    }

    public function update(Request $request, $key_name)
    {
        $contentItem = LandingContent::where('key_name', $key_name)->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
        ]);

        $contentVal = $request->content;
        if (is_array($contentVal) || is_object($contentVal)) {
            $contentVal = json_encode($contentVal);
        }

        $contentItem->update([
            'title' => $request->title,
            'content' => $contentVal
        ]);

        return response()->json([
            'message' => "Konten section '{$key_name}' berhasil diperbarui.",
            'data' => [
                'key_name' => $key_name,
                'title' => $contentItem->title,
                'content' => is_array($request->content) || is_object($request->content) ? json_decode($contentItem->content, true) : $contentItem->content
            ]
        ]);
    }
}
