<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
            ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->orderBy('name')
            ->paginate(10);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->organization) {
            $currentUserCount = User::count();
            if ($currentUserCount >= $currentUser->organization->max_users) {
                return response()->json([
                    'message' => "Kuota user untuk organisasi Anda telah mencapai batas maksimal ({$currentUser->organization->max_users} user)."
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in([
                'superadmin',
                'project_manager',
                'staff',
            ])],
            'status' => 'required|in:active,inactive',
            'password' => 'required|string|min:8',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', Rule::in([
                'superadmin',
                'project_manager',
                'staff',
            ])],
            'status' => 'sometimes|in:active,inactive',
            'password' => 'nullable|string|min:8',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus.']);
    }
}
