<?php

namespace App\Http\Controllers;

use App\Models\LandingContent;
use App\Models\ContactMessage;
use App\Models\Organization;
use App\Models\User;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicLandingController extends Controller
{
    public function faqs()
    {
        $faqs = Faq::orderBy('order_number')->get();
        return response()->json($faqs);
    }
    public function contents()
    {
        $contents = LandingContent::all()->keyBy('key_name');

        // Parse JSON content for sections like features, pricing, contact_info
        $formatted = [];
        foreach ($contents as $key => $content) {
            $decoded = json_decode($content->content, true);
            $formatted[$key] = [
                'title' => $content->title,
                'content' => json_last_error() === JSON_ERROR_NONE ? $decoded : $content->content
            ];
        }

        return response()->json($formatted);
    }

    public function contact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $message = ContactMessage::create($request->only(['name', 'email', 'subject', 'message']));

        return response()->json([
            'message' => 'Pesan Anda berhasil dikirim ke Admin. Terima kasih!',
            'data' => $message
        ], 201);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:organizations,email',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
            'plan'           => 'nullable|string|in:free,business,enterprise',
        ]);

        $plan = $request->input('plan', 'free');
        $status = $plan === 'free' ? 'active' : 'pending';

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $organization = DB::transaction(function () use ($request, $slug, $plan, $status) {
            $org = Organization::create([
                'name'       => $request->name,
                'slug'       => $slug,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address,
                'plan'       => $plan,
                'status'     => $status,
            ]);

            User::create([
                'organization_id' => $org->id,
                'name'            => $request->admin_name,
                'email'           => $request->admin_email,
                'password'        => Hash::make($request->admin_password),
                'role'            => 'superadmin',
                'status'          => 'active',
            ]);

            return $org;
        });

        return response()->json([
            'message'      => $status === 'active' 
                ? 'Registrasi organisasi Event Organizer berhasil! Silakan login.' 
                : 'Registrasi organisasi berhasil! Pembayaran sedang diverifikasi, menunggu persetujuan Admin Backoffice.',
            'organization' => $organization
        ], 201);
    }
}
