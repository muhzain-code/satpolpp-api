<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Queue\NullQueue;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // $anggota = Anggota::create([
        //     'kode_anggota'   => null,
        //     'nik'            => null,
        //     'nama'           => 'Super Administrator',
        //     'jenis_kelamin'  => null,
        //     'alamat'         => null,
        //     'status'         => 'aktif',
        // ]);

        $user = User::create([
            'name'        => 'Super Admin',
            'email'       => 'superadmin@example.com',
            'password'    => Hash::make('password'),
            'anggota_id'  => null,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }

        $this->command->info('✅ Super Admin berhasil dibuat: superadmin@example.com / password');
    }
}
