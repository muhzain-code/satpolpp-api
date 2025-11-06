<?php

namespace App\Services\Auth;

use App\Exceptions\CustomException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'anggota_id' => $data['anggota_id'] ?? null,
            ]);

            $user->assignRole($data['role']);

            return [
                'success' => true,
                'message' => 'User Berhasil di Tambahkan',
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('Gagal menambah user', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat membuat user',
                'data' => null
            ];
        }
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
}
