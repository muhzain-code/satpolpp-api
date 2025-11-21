<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriPengaduanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Gangguan Ketertiban Umum', 'keterangan' => 'Keributan, kebisingan, dll'],
            ['nama' => 'PKL Liar', 'keterangan' => 'Pedagang kaki lima tidak sesuai aturan'],
            ['nama' => 'Bangunan Liar', 'keterangan' => 'Pelanggaran ruang publik'],
            ['nama' => 'Pelanggaran Perda', 'keterangan' => null],
            ['nama' => 'Lainnya', 'keterangan' => null],
        ];

        foreach ($data as $d) {
            DB::table('kategori_pengaduan')->insert([
                'nama' => $d['nama'],
                'keterangan' => $d['keterangan'],
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }
}
