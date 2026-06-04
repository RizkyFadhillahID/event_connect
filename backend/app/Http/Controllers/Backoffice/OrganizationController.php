<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $organizations = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($organizations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:organizations,email',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'plan'           => 'nullable|string|in:free,business,enterprise',
            'max_users'      => 'nullable|integer|min:1',
            'max_events'     => 'nullable|integer|min:1',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $organization = DB::transaction(function () use ($request, $slug) {
            $org = Organization::create([
                'name'       => $request->name,
                'slug'       => $slug,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address,
                'plan'       => $request->plan ?? 'free',
                'max_users'  => $request->max_users,
                'max_events' => $request->max_events,
                'status'     => 'active',
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
            'message'      => 'Organisasi berhasil dibuat.',
            'organization' => $organization
        ], 201);
    }

    public function show($id)
    {
        $organization = Organization::findOrFail($id);

        $totalUsers = User::where('organization_id', $organization->id)->count();
        $activeUsers = User::where('organization_id', $organization->id)->where('status', 'active')->count();

        $totalEvents = Event::where('organization_id', $organization->id)->count();
        $activeEvents = Event::where('organization_id', $organization->id)
            ->whereIn('status', ['active', 'ongoing'])
            ->count();
        $completedEvents = Event::where('organization_id', $organization->id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'organization' => $organization,
            'stats'        => [
                'total_users'      => $totalUsers,
                'active_users'     => $activeUsers,
                'total_events'     => $totalEvents,
                'active_events'    => $activeEvents,
                'completed_events' => $completedEvents,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:organizations,email,' . $organization->id,
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'plan'           => 'required|string|in:free,business,enterprise',
            'max_users'      => 'required|integer|min:1',
            'max_events'     => 'required|integer|min:1',
            'status'         => 'required|in:active,suspended,inactive',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (Organization::where('slug', $slug)->where('id', '!=', $organization->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $organization->update([
            'name'       => $request->name,
            'slug'       => $slug,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'address'    => $request->address,
            'plan'       => $request->plan,
            'max_users'  => $request->max_users,
            'max_events' => $request->max_events,
            'status'     => $request->status,
        ]);

        return response()->json([
            'message'      => 'Organisasi berhasil diupdate.',
            'organization' => $organization
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $request->validate([
            'status' => 'required|in:active,suspended,inactive',
        ]);

        $organization->update(['status' => $request->status]);

        return response()->json([
            'message'      => 'Status organisasi berhasil diubah.',
            'organization' => $organization
        ]);
    }

    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);

        if ($organization->slug === 'default') {
            return response()->json(['message' => 'Organisasi default tidak dapat dihapus.'], 400);
        }

        $organization->update(['status' => 'inactive']);

        return response()->json(['message' => 'Organisasi dinonaktifkan.']);
    }
}
