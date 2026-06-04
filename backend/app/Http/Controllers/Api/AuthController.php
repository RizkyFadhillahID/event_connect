<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user = Auth::user();

        if ($user->status === 'inactive') {
            Auth::logout();
            return response()->json(['message' => 'Akun Anda tidak aktif.'], 403);
        }

        $user->load('organization');
        if ($user->organization && $user->organization->status !== 'active') {
            Auth::logout();
            $msg = 'Organisasi Anda sedang dinonaktifkan atau ditangguhkan.';
            if ($user->organization->status === 'pending') {
                $msg = 'Pendaftaran organisasi Anda (Paket ' . ucfirst($user->organization->plan) . ') sedang menunggu verifikasi pembayaran dan persetujuan Admin Backoffice.';
            }
            return response()->json(['message' => $msg], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('organization'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $emailChanged = $data['email'] !== $user->email;
        $passwordChanged = !empty($data['password']);

        if ($emailChanged || $passwordChanged) {
            if (empty($data['current_password'])) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini wajib diisi untuk mengubah email atau password.'],
                ]);
            }

            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak cocok.'],
                ]);
            }
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];

        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json($user->load('organization'));
    }
}
