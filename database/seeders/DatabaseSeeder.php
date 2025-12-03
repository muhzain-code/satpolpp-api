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
        // $user = User::create([
        //     'name'        => 'Super Admin',
        //     'email'       => 'superadmin@example.com',
        //     'password'    => Hash::make('password'),
        //     'anggota_id'  => null,
        // ]);

        $this->call([
            RoleSeeder::class,
            // JabatanSeeder::class,
            // UnitSeeder::class,
            // AnggotaSeeder::class,
            KategoriPengaduanSeeder::class,
            PengaduanSeeder::class,
            KontenSeeder::class,
            GaleriSeeder::class,
        ]);

        // $user->update(['anggota_id' => 1]);
        // $superAdminRole = Role::where('name', 'super_admin')->first();
        // if ($superAdminRole) {
        //     $user->assignRole($superAdminRole);
        // }

        // $this->command->info('✅ Super Admin berhasil dibuat: superadmin@example.com / password');
    }
}
