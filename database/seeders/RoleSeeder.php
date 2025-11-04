<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus cache Spatie agar tidak bentrok
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'super_admin',
            'admin_dinas',
            'operator_pengaduan',
            'komandan_regu',
            'anggota_satpolpp',
            'ppns',
            'humas',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('✅ RoleSeeder: Role dasar berhasil dibuat tanpa kolom tambahan.');
    }
}
