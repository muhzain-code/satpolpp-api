<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Anggota\Unit;
use App\Models\Anggota\Anggota;
use App\Models\Anggota\Jabatan;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Services\NomorGeneratorService;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /* =================================================
         * 1. Define Role Dasar
         * ================================================= */
        $roles = [
            'super_admin',
            'admin_dinas',
            'operator',
            'komandan_regu',
            'anggota_regu',
            'ppns',
            'humas',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role],
                ['guard_name' => 'web']
            );
        }

        /* =================================================
         * 2. Create jabatan
         * ================================================= */
        $jabatanList = [
            'Komandan Regu',
            'Wakil Komandan',
            'Anggota Regu',
            'PPNS',
            'Operator',
            'Admin Dinas',
            'Humas',
        ];

        $jabatanIds = [];
        foreach ($jabatanList as $jabatan) {
            $created = Jabatan::updateOrCreate(
                ['nama' => $jabatan],
                ['keterangan' => $jabatan]
            );
            $jabatanIds[$jabatan] = $created->id;
        }

        /* =================================================
         * 3. Create unit / regu
         * ================================================= */
        $unitList = ['Regu A', 'Regu B', 'Regu C'];

        $unitIds = [];
        foreach ($unitList as $unit) {
            $created = Unit::updateOrCreate(
                ['nama' => $unit],
                ['keterangan' => "Satuan tugas {$unit}"]
            );
            $unitIds[] = $created->id;
        }

        /* =================================================
 * 4. Create anggota sample
 * ================================================= */
        $generator = app(NomorGeneratorService::class);

        $anggotaSamples = [
            [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Komandan Regu',
                'unit_index' => 0,
            ],
            [
                'nama' => 'Agus Wijaya',
                'jabatan' => 'Anggota Regu',
                'unit_index' => 0,
            ],
            [
                'nama' => 'Dewi Mulyani',
                'jabatan' => 'PPNS',
                'unit_index' => null,
            ],
        ];

        $anggotaIds = [];
        foreach ($anggotaSamples as $i => $a) {

            $kodeAnggota = $generator->generateKodeAnggota();   // <-- PAKAI GENERATOR

            $anggota = Anggota::updateOrCreate(
                ['nama' => $a['nama']],
                [
                    'kode_anggota' => $kodeAnggota, // <-- BUKAN UUID LAGI
                    'nik' => fake()->numerify('################'),
                    'nip' => fake()->numerify('##################'),
                    'nama' => $a['nama'],
                    'jenis_kelamin' => 'L',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '1985-01-01',
                    'jabatan_id' => $jabatanIds[$a['jabatan']],
                    'unit_id' => $a['unit_index'] !== null ? $unitIds[$a['unit_index']] : null,
                    'status' => 'aktif',
                    'jenis_kepegawaian' => 'asn',
                ]
            );

            $anggotaIds[$a['nama']] = $anggota->id;
        }

        /* =================================================
         * 5. Create User + Assign Role + Set anggota_id
         * ================================================= */
        $userList = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'super_admin',
                'anggota' => null,
            ],
            [
                'name' => 'Admin Dinas',
                'email' => 'admin@example.com',
                'role' => 'admin_dinas',
                'anggota' => null,
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@example.com',
                'role' => 'operator',
                'anggota' => null,
            ],
            [
                'name' => 'Komandan Regu',
                'email' => 'komandan@example.com',
                'role' => 'komandan_regu',
                'anggota' => 'Budi Santoso',
            ],
            [
                'name' => 'Anggota Regu',
                'email' => 'anggota@example.com',
                'role' => 'anggota_regu',
                'anggota' => 'Agus Wijaya',
            ],
            [
                'name' => 'PPNS',
                'email' => 'ppns@example.com',
                'role' => 'ppns',
                'anggota' => 'Dewi Mulyani',
            ],
            [
                'name' => 'Humas',
                'email' => 'humas@example.com',
                'role' => 'humas',
                'anggota' => null,
            ],
        ];

        foreach ($userList as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'anggota_id' => $u['anggota'] ? $anggotaIds[$u['anggota']] : null,
                ]
            );

            $user->assignRole($u['role']);
        }

        $this->command->info('🔥 MasterOrganizationSeeder berhasil dijalankan: Role, Jabatan, Unit, Anggota, User lengkap!');
        $this->command->info('➡ User login contoh: email = superadmin@example.com & password = password');
    }
    // public function run(): void
    // {
    //     // Hapus cache Spatie agar tidak bentrok
    //     app()[PermissionRegistrar::class]->forgetCachedPermissions();

    //     $roles = [
    //         'super_admin',
    //         'admin_dinas',
    //         'operator',
    //         'komandan_regu',
    //         'anggota_regu',
    //         'ppns',
    //         'humas',
    //     ];

    //     foreach ($roles as $role) {
    //         Role::updateOrCreate(
    //             ['name' => $role],
    //             ['guard_name' => 'web']
    //         );
    //     }

    //     $this->command->info('✅ RoleSeeder: Role dasar berhasil dibuat tanpa kolom tambahan.');
    // }
}
