<?php

namespace Database\Seeders;

use App\Models\Humas\Konten;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Untuk membuat slug
use Carbon\Carbon;

class KontenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID user pertama untuk created_by (atau set manual angka 1)
        $userId = User::first()->id ?? 1;
        $now = Carbon::now();

        // ==========================================
        // 1. DATA BERITA (3 Data Statis)
        // ==========================================
        $beritaData = [
            [
                'judul' => 'Rapat Koordinasi Evaluasi Kinerja Triwulan III Tahun 2025',
                'isi'   => '<p>Dinas mengadakan rapat koordinasi untuk mengevaluasi capaian kinerja selama triwulan ketiga tahun anggaran 2025. Rapat ini dipimpin langsung oleh Kepala Dinas dan dihadiri oleh seluruh pejabat struktural.</p><p>Dalam arahannya, Kepala Dinas menekankan pentingnya percepatan serapan anggaran dan peningkatan kualitas pelayanan publik.</p>',
            ],
            [
                'judul' => 'Kegiatan Penertiban PKL di Kawasan Alun-Alun Kota',
                'isi'   => '<p>Satuan Polisi Pamong Praja (Satpol PP) kembali melakukan penertiban terhadap Pedagang Kaki Lima (PKL) yang berjualan di bahu jalan kawasan Alun-Alun Kota pagi ini.</p><p>Penertiban berjalan kondusif dengan mengedepankan pendekatan persuasif kepada para pedagang agar mematuhi Peraturan Daerah tentang Ketertiban Umum.</p>',
            ],
            [
                'judul' => 'Sosialisasi Peraturan Daerah Terbaru tentang Retribusi',
                'isi'   => '<p>Pemerintah Kota menggelar sosialisasi mengenai perubahan tarif retribusi pelayanan pasar. Sosialisasi ini bertujuan agar masyarakat dan pelaku usaha memahami dasar hukum dan mekanisme pembayaran terbaru.</p>',
            ]
        ];

        foreach ($beritaData as $item) {
            Konten::create([
                'tipe'             => 'berita',
                'judul'            => $item['judul'],
                'slug'             => Str::slug($item['judul']),
                'isi'              => $item['isi'],
                'path_gambar'      => null,
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
                // Field agenda null
                'lokasi'           => null,
                'tanggal_kegiatan' => null,
                'waktu_mulai'      => null,
                'waktu_selesai'    => null,
            ]);
        }


        // ==========================================
        // 2. DATA AGENDA (3 Data Statis)
        // ==========================================
        $agendaData = [
            [
                'judul'            => 'Apel Besar Hari Ulang Tahun Satpol PP',
                'isi'              => '<p>Seluruh anggota wajib hadir mengenakan Pakaian Dinas Upacara (PDU).</p>',
                'lokasi'           => 'Halaman Kantor Walikota',
                'tanggal_kegiatan' => $now->copy()->addDays(3)->format('Y-m-d'),
                'waktu_mulai'      => '07:30:00',
                'waktu_selesai'    => '10:00:00',
            ],
            [
                'judul'            => 'Bimbingan Teknis Penggunaan Aplikasi E-Kinerja',
                'isi'              => '<p>Peserta diharapkan membawa laptop masing-masing.</p>',
                'lokasi'           => 'Aula Gedung B Lt. 2',
                'tanggal_kegiatan' => $now->copy()->addDays(7)->format('Y-m-d'),
                'waktu_mulai'      => '09:00:00',
                'waktu_selesai'    => '15:00:00',
            ],
            [
                'judul'            => 'Rapat Paripurna DPRD',
                'isi'              => '<p>Agenda pembahasan RAPBD Tahun 2026.</p>',
                'lokasi'           => 'Gedung DPRD',
                'tanggal_kegiatan' => $now->copy()->addDays(10)->format('Y-m-d'),
                'waktu_mulai'      => '13:00:00',
                'waktu_selesai'    => '16:00:00',
            ],
        ];

        foreach ($agendaData as $item) {
            Konten::create([
                'tipe'             => 'agenda',
                'judul'            => $item['judul'],
                'slug'             => Str::slug($item['judul']),
                'isi'              => $item['isi'],
                'path_gambar'      => null,
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
                // Field khusus agenda
                'lokasi'           => $item['lokasi'],
                'tanggal_kegiatan' => $item['tanggal_kegiatan'],
                'waktu_mulai'      => $item['waktu_mulai'],
                'waktu_selesai'    => $item['waktu_selesai'],
            ]);
        }


        // ==========================================
        // 3. DATA HIMBAUAN (3 Data Statis)
        // ==========================================
        $himbauanData = [
            [
                'judul' => 'Waspada Cuaca Ekstrem dan Angin Kencang',
                'isi'   => '<p>Dihimbau kepada seluruh masyarakat untuk mewaspadai potensi hujan lebat disertai angin kencang dalam sepekan ke depan. Hindari berteduh di bawah pohon besar yang rawan tumbang.</p>',
            ],
            [
                'judul' => 'Larangan Membuang Sampah Sembarangan',
                'isi'   => '<p>Mari jaga kebersihan lingkungan kita. Membuang sampah sembarangan di sungai dapat menyebabkan banjir dan merupakan pelanggaran Perda No. 5 Tahun 2020. Pelanggar dapat dikenakan sanksi denda.</p>',
            ],
            [
                'judul' => 'Himbauan Pembayaran PBB-P2 Sebelum Jatuh Tempo',
                'isi'   => '<p>Segera lunasi Pajak Bumi dan Bangunan Perdesaan dan Perkotaan (PBB-P2) Anda sebelum tanggal 30 September untuk menghindari denda administrasi. Orang bijak taat pajak.</p>',
            ],
        ];

        foreach ($himbauanData as $item) {
            Konten::create([
                'tipe'             => 'himbauan',
                'judul'            => $item['judul'],
                'slug'             => Str::slug($item['judul']),
                'isi'              => $item['isi'],
                'path_gambar'      => null,
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
                // Field agenda null
                'lokasi'           => null,
                'tanggal_kegiatan' => null,
                'waktu_mulai'      => null,
                'waktu_selesai'    => null,
            ]);
        }
    }
}
