<?php

namespace App\Services\Auth;

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
}
