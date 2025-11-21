<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\NomorGeneratorService;

class PengaduanSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(NomorGeneratorService::class);

        $data = [
            [
                'nama_pelapor' => 'Andi',
                'kontak_pelapor' => '08123456789',
                'kategori_id' => 1,
                'deskripsi' => 'Terdapat keributan di area taman kota.',
                'lat' => -6.917464,
                'lng' => 107.619125,
                'alamat' => 'Taman Kota Bandung',
                'status' => 'diterima',
            ],
            [
                'nama_pelapor' => 'Rina',
                'kontak_pelapor' => '082233445566',
                'kategori_id' => 2,
                'deskripsi' => 'PKL menutup trotoar di Jalan Asia Afrika.',
                'lat' => -6.921851,
                'lng' => 107.607656,
                'alamat' => 'Jl. Asia Afrika',
                'status' => 'diterima',
            ],
        ];

        foreach ($data as $item) {
            DB::table('pengaduan')->insert(array_merge($item, [
                'nomor_tiket' => $generator->generateNomorTiket(),
            ]));
        }
    }
}
