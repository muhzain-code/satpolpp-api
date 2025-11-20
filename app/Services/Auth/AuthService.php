<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        $authUser = Auth::user();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'anggota_id' => $data['anggota_id'] ?? null,
        ]);

        $user->assignRole($data['role']);

        activity('auth')
            ->causedBy($authUser)
            ->performedOn($user)
            ->event('register')
            ->withProperties([
                'user_id'     => $user->id,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'ip'          => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ])
            ->log("User '{$user->email}' berhasil diregistrasi oleh '{$authUser->email}'");

        return [
            'success' => true,
            'message' => 'User Berhasil di Tambahkan',
            'data' => $user
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw new CustomException('Email tidak aktif', 403);
        }

        if (!Hash::check($data['password'], $user->password)) {
            throw new CustomException('Password salah', 401);
        }

        $token = $user->createToken('auth_token', [$user->getRoleNames()->first()])->plainTextToken;

        $user->tokens()->latest()->first()->forceFill([
            'expires_at' => now()->addHours(8)
        ])->save();

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->withProperties([
                'user_id'     => $user->id,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'ip'          => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ])
            ->log("Pengguna '{$user->email}' berhasil login");


        return [
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'anggota_id' => $user->anggota_id ?? null
                ],
                'token' => $token,
            ],
        ];
    }

    public function logout(User $user): array
    {
        $user->tokens()->delete();

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('logout')
            ->withProperties([
                'user_id'     => $user->id,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'ip'          => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ])
            ->log("Pengguna '{$user->email}' berhasil logout");

        return [
            'success' => true,
            'message' => 'Logout berhasil',
            'data' => null,
        ];
    }
}
