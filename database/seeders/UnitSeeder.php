<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Regu A', 'keterangan' => 'Regu patroli siang'],
            ['nama' => 'Regu B', 'keterangan' => 'Regu patroli malam'],
            ['nama' => 'Regu C', 'keterangan' => 'Regu siaga'],
            ['nama' => 'Trantibum', 'keterangan' => 'Ketertiban umum'],
            ['nama' => 'Gakda', 'keterangan' => 'Penegakan Perda'],
            ['nama' => 'Linmas', 'keterangan' => 'Perlindungan masyarakat'],
        ];

        foreach ($data as $d) {
            DB::table('unit')->insert([
                'nama' => $d['nama'],
                'keterangan' => $d['keterangan'],
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }
}
