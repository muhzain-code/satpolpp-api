<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Kasatpol PP', 'keterangan' => 'Kepala Satuan Polisi Pamong Praja'],
            ['nama' => 'Sekretaris', 'keterangan' => 'Sekretaris Satpol PP'],
            ['nama' => 'Kabid Penegakan Peraturan Daerah', 'keterangan' => null],
            ['nama' => 'Kabid Ketertiban Umum', 'keterangan' => null],
            ['nama' => 'Kasie Operasi & Pengendalian', 'keterangan' => null],
            ['nama' => 'Komandan Regu', 'keterangan' => 'Danru'],
            ['nama' => 'Anggota', 'keterangan' => 'Personil lapangan'],
        ];

        foreach ($data as $d) {
            DB::table('jabatan')->insert([
                'nama' => $d['nama'],
                'keterangan' => $d['keterangan'],
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }
}
