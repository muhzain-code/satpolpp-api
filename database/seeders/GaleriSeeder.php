<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Carbon\Carbon;

class GaleriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            [
                'judul' => 'Dokumentasi Rapat Tahunan',
                'path_file' => 'uploads/galeri/rapat-tahunan.jpg',
                'tipe' => 'foto',
                'status' => true,
                'created_by' => 1, // Pastikan user id 1 ada
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'judul' => 'Video Profil Organisasi',
                'path_file' => 'uploads/galeri/video-profil.mp4',
                'tipe' => 'video',
                'status' => true,
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'judul' => 'Kegiatan Bakti Sosial',
                'path_file' => 'uploads/galeri/baksos-2024.jpg',
                'tipe' => 'foto',
                'status' => true,
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'judul' => 'Foto Bersama Staff',
                'path_file' => 'uploads/galeri/staff-full-team.jpg',
                'tipe' => 'foto',
                'status' => false, // Contoh tidak aktif
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('galeri')->insert($data);
    }
}
