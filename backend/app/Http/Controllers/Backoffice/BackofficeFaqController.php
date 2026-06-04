<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class BackofficeFaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('order_number')->get();
        return response()->json($faqs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question'     => 'required|string|max:255',
            'answer'       => 'required|string',
            'order_number' => 'nullable|integer',
        ]);

        if (!isset($validated['order_number'])) {
            $validated['order_number'] = Faq::max('order_number') + 1;
        }

        $faq = Faq::create($validated);

        return response()->json([
            'message' => 'FAQ baru berhasil ditambahkan.',
            'data'    => $faq
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'question'     => 'sometimes|required|string|max:255',
            'answer'       => 'sometimes|required|string',
            'order_number' => 'nullable|integer',
        ]);

        $faq->update($validated);

        return response()->json([
            'message' => 'FAQ berhasil diperbarui.',
            'data'    => $faq
        ]);
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'message' => 'FAQ berhasil dihapus.'
        ]);
    }
}
