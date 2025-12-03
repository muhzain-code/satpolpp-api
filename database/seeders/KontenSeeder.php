<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Untuk membuat slug
use Carbon\Carbon;

class KontenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            [
                'tipe' => 'berita',
                'judul' => 'Peresmian Gedung Serbaguna Baru',
                'slug' => Str::slug('Peresmian Gedung Serbaguna Baru'),
                'isi' => '<p>Gedung serbaguna baru telah diresmikan oleh Kepala Dinas terkait pada hari Senin lalu. Gedung ini diharapkan dapat menunjang aktivitas warga.</p>',
                'path_gambar' => 'uploads/konten/gedung-baru.jpg',
                'tampilkan_publik' => true,
                'published_at' => Carbon::now(),
                'created_by' => 1, // Pastikan user id 1 ada, atau ganti null
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipe' => 'agenda',
                'judul' => 'Workshop Digital Marketing 2025',
                'slug' => Str::slug('Workshop Digital Marketing 2025'),
                'isi' => '<p>Ikuti pelatihan digital marketing yang akan dilaksanakan di Aula Utama mulai pukul 08.00 WIB. Terbuka untuk umum.</p>',
                'path_gambar' => 'uploads/konten/workshop.jpg',
                'tampilkan_publik' => true,
                'published_at' => Carbon::now()->subDays(2), // 2 hari lalu
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipe' => 'himbauan',
                'judul' => 'Waspada Cuaca Ekstrem',
                'slug' => Str::slug('Waspada Cuaca Ekstrem'),
                'isi' => '<p>Diharapkan kepada seluruh warga untuk tetap waspada terhadap potensi hujan lebat dan angin kencang dalam 3 hari ke depan.</p>',
                'path_gambar' => null, // Contoh tanpa gambar
                'tampilkan_publik' => true,
                'published_at' => Carbon::now(),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipe' => 'berita',
                'judul' => 'Update Sistem Informasi Versi 2.0',
                'slug' => Str::slug('Update Sistem Informasi Versi 2.0'),
                'isi' => '<p>Sistem akan mengalami maintenance pada akhir pekan ini untuk peningkatan performa.</p>',
                'path_gambar' => 'uploads/konten/maintenance.png',
                'tampilkan_publik' => false, // Contoh draft/belum publik
                'published_at' => null,
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('konten')->insert($data);
    }
}
