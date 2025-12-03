<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegulasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // ---------------------------------------------------------
        // 1. SEED KATEGORI REGULASI
        // ---------------------------------------------------------
        $categories = [
            [
                'nama' => 'Undang-Undang (UU)',
                'keterangan' => 'Peraturan Perundang-undangan yang dibentuk oleh Dewan Perwakilan Rakyat dengan persetujuan bersama Presiden.',
            ],
            [
                'nama' => 'Peraturan Pemerintah (PP)',
                'keterangan' => 'Peraturan Perundang-undangan yang ditetapkan oleh Presiden untuk menjalankan Undang-Undang sebagaimana mestinya.',
            ],
            [
                'nama' => 'Peraturan Presiden (Perpres)',
                'keterangan' => 'Peraturan Perundang-undangan yang ditetapkan oleh Presiden untuk menjalankan perintah Peraturan Perundang-undangan yang lebih tinggi atau dalam menyelenggarakan kekuasaan pemerintahan.',
            ],
            [
                'nama' => 'Peraturan Menteri (Permen)',
                'keterangan' => 'Peraturan yang ditetapkan oleh Menteri berdasarkan materi muatan dalam rangka penyelenggaraan urusan tertentu dalam pemerintahan.',
            ],
            [
                'nama' => 'Surat Edaran (SE)',
                'keterangan' => 'Naskah dinas yang memuat pemberitahuan tentang hal tertentu yang dianggap mendesak.',
            ]
        ];

        foreach ($categories as $category) {
            DB::table('kategori_regulasi')->updateOrInsert(
                ['nama' => $category['nama']], // Cek unique berdasarkan nama
                [
                    'keterangan' => $category['keterangan'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }


        // ---------------------------------------------------------
        // 2. SEED DATA REGULASI
        // ---------------------------------------------------------

        // Ambil ID kategori yang baru saja di-seed/pastikan ada
        $catUU = DB::table('kategori_regulasi')->where('nama', 'like', 'Undang-Undang%')->value('id');
        $catPP = DB::table('kategori_regulasi')->where('nama', 'like', 'Peraturan Pemerintah%')->value('id');
        $catSE = DB::table('kategori_regulasi')->where('nama', 'like', 'Surat Edaran%')->value('id');

        if (!$catUU || !$catPP) {
            $this->command->error('Kategori tidak ditemukan, gagal seeding regulasi.');
            return;
        }

        // Data dummy regulasi
        $regulasiData = [
            [
                'kode' => 'UU/2024/01',
                'judul' => 'Undang-Undang Nomor 1 Tahun 2024 tentang Informasi Publik',
                'tahun' => 2024,
                'kategori_regulasi_id' => $catUU,
                'ringkasan' => 'Mengatur tentang keterbukaan informasi publik dan hak masyarakat untuk mendapatkan informasi.',
                'path_pdf' => 'regulasi/dummy-uu-2024.pdf',
                'aktif' => true,
                'tampilkan_publik' => true, // Tampil di publik
                'created_by' => 1, // Pastikan user ID 1 ada atau ubah ke null
            ],
            [
                'kode' => 'PP/2023/15',
                'judul' => 'Peraturan Pemerintah Nomor 15 Tahun 2023 tentang Tata Kelola',
                'tahun' => 2023,
                'kategori_regulasi_id' => $catPP,
                'ringkasan' => 'Pedoman teknis pelaksanaan tata kelola organisasi yang efektif.',
                'path_pdf' => null,
                'aktif' => true,
                'tampilkan_publik' => false, // Internal saja
                'created_by' => 1,
            ],
            [
                'kode' => 'SE/INTERNAL/2024/005',
                'judul' => 'Surat Edaran Jam Kerja Selama Ramadhan',
                'tahun' => 2024,
                'kategori_regulasi_id' => $catSE,
                'ringkasan' => 'Penyesuaian jam kerja karyawan selama bulan suci Ramadhan.',
                'path_pdf' => null,
                'aktif' => false, // Tidak aktif
                'tampilkan_publik' => true,
                'created_by' => 1,
            ],
        ];

        foreach ($regulasiData as $data) {
            DB::table('regulasi')->updateOrInsert(
                ['kode' => $data['kode']], // Cek unique kode agar tidak duplikat
                array_merge($data, [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }

    }
}
