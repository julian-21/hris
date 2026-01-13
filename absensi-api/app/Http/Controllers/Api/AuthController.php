<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|exists:roles,name',
            'company' => 'nullable|string|max:255',
            'atasan_id' => 'nullable|exists:users,id',
            'jatah_cuti_tambahan' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company' => $request->company,
            'atasan_id' => $request->atasan_id,
            'jatah_cuti_tambahan' => $request->jatah_cuti_tambahan ?? 0,
        ]);

        // Assign role (default: Karyawan)
        $user->assignRole($request->role ?? 'Karyawan');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['roles', 'atasan']),
            'token' => $token,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Delete old tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['roles', 'atasan', 'bawahan']),
            'token' => $token,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['roles', 'atasan', 'bawahan']);

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}