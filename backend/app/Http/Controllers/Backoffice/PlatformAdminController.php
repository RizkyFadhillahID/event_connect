<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\PlatformAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PlatformAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformAdmin::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role != '') {
            $query->where('role', $request->role);
        }

        $admins = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:platform_admins,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:owner,admin,support',
            'status'   => 'required|in:active,inactive',
        ]);

        $admin = PlatformAdmin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => $request->status,
        ]);

        return response()->json([
            'message' => 'Admin platform berhasil dibuat.',
            'admin'   => $admin
        ], 201);
    }

    public function show($id)
    {
        $admin = PlatformAdmin::findOrFail($id);
        return response()->json($admin);
    }

    public function update(Request $request, $id)
    {
        $admin = PlatformAdmin::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:platform_admins,email,' . $admin->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'required|in:owner,admin,support',
            'status'   => 'required|in:active,inactive',
        ]);

        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'role'   => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return response()->json([
            'message' => 'Admin platform berhasil diupdate.',
            'admin'   => $admin
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $admin = PlatformAdmin::findOrFail($id);
        $currentUser = $request->user();

        if ($admin->id === $currentUser->id) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 400);
        }

        if ($admin->role === 'owner' && PlatformAdmin::where('role', 'owner')->count() <= 1) {
            return response()->json(['message' => 'Harus ada setidaknya satu owner platform.'], 400);
        }

        $admin->delete();

        return response()->json(['message' => 'Admin platform berhasil dihapus.']);
    }
}
