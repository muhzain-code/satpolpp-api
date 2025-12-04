<?php

namespace App\Services\Auth;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{

    public function getAll($perPage, $currentPage, $request)
    {
        try {
            $users = User::with('roles'); // penting: eager load roles

            // Pencarian
            if ($request->filled('search')) {
                $search = $request->search;
                $users->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Pagination
            $results = $users->paginate($perPage, ['*'], 'page', $currentPage);

            // Transform Data
            $data = $results->getCollection()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'name'  => $item->name,
                    'email' => $item->email,
                    'roles' => $item->roles->pluck('name')->first(),
                ];
            });

            return [
                'message' => 'Data user berhasil ditampilkan',
                'data'    => $data,
                'meta'    => [
                    'current_page' => $results->currentPage(),
                    'per_page'     => $results->perPage(),
                    'total'        => $results->total(),
                    'last_page'    => $results->lastPage(),
                    'from'         => $results->firstItem(),
                    'to'           => $results->lastItem(),
                ]
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data user', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('User gagal ditampilkan');
        }
    }

    public function findById($id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);

            $userData = [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'anggota_id'    => $user->anggota_id,
                'role'       => $user->roles->pluck('name')->first(),
            ];

            return [
                'message' => 'Detail user berhasil ditampilkan',
                'data'    => $userData
            ];
        } catch (\Throwable $e) {

            Log::error('Error findById AuthService', [
                'id'      => $id,
                'message' => $e->getMessage(),
            ]);

            throw new CustomException('Data user tidak ditemukan');
        }
    }

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

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);

            if (!$user) {
                throw new CustomException('User tidak ditemukan');
            }

            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'anggota_id' => $data['anggota_id'] ?? null,
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (isset($data['role'])) {
                $user->syncRoles($data['role']);
            }

            DB::commit();

            return [
                'message' => 'Data user berhasil diperbarui',
                'data'  => $user->fresh(['roles'])
            ];
        } catch (Exception $e) {
            Log::error('Error update user', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal mengupdate user');
        }
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

    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        return Password::reset($data, function (User $user, string $pass) {
            $user->password = Hash::make($pass);
            $user->setRememberToken(Str::random(60));
            $user->tokens()->delete();
            $user->save();
        });
    }

    public function changePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            abort(422, 'Current password is incorrect.');
        }

        $user->password = Hash::make($new);
        $user->setRememberToken(Str::random(60));
        $user->tokens()->delete();
        $user->save();

        activity('auth')
            ->event('password_changed')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Password pengguna '{$user->email}' berhasil diubah");
    }
}
