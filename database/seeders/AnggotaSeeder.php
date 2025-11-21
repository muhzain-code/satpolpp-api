<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\NomorGeneratorService;

class AnggotaSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(NomorGeneratorService::class);

        $data = [
            [
                'nik' => '3201010101010001',
                'nip' => '198001012005011001',
                'nama' => 'Budi Santoso',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1980-01-01',
                'alamat' => 'Jl. Sukajadi No. 12',
                'no_hp' => '081234567890',
                'foto' => null,
                'jabatan_id' => 1,
                'unit_id' => 1,
                'status' => 'aktif',
                'jenis_kepegawaian' => 'asn',
            ],
            [
                'nik' => '3201010101010002',
                'nip' => null,
                'nama' => 'Siti Aminah',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Garut',
                'tanggal_lahir' => '1988-04-12',
                'alamat' => 'Jl. Melati No. 23',
                'no_hp' => '082233445566',
                'foto' => null,
                'jabatan_id' => 7,
                'unit_id' => 2,
                'status' => 'aktif',
                'jenis_kepegawaian' => 'nonasn',
            ],
        ];

        foreach ($data as $item) {
            DB::table('anggota')->insert(array_merge($item, [
                'kode_anggota' => $generator->generateKodeAnggota(),
                'created_by' => 1,
                'updated_by' => 1,
            ]));
        }
    }
}
