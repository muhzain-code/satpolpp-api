<?php

namespace Database\Seeders;

use App\Models\Humas\Agenda;
use App\Models\Humas\Berita;
use App\Models\Humas\Himbauan;
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
        // 1. DATA BERITA
        // Schema: judul, slug, Kategori, isi, path_gambar, tampilkan_publik, published_at, created_by
        // ==========================================
        $beritaData = [
            [
                'judul'    => 'Rapat Koordinasi Evaluasi Kinerja Triwulan III Tahun 2025',
                'kategori' => 'Kegiatan',
                'isi'      => '<p>Dinas mengadakan rapat koordinasi untuk mengevaluasi capaian kinerja selama triwulan ketiga tahun anggaran 2025. Rapat ini dipimpin langsung oleh Kepala Dinas dan dihadiri oleh seluruh pejabat struktural.</p><p>Dalam arahannya, Kepala Dinas menekankan pentingnya percepatan serapan anggaran dan peningkatan kualitas pelayanan publik.</p>',
            ],
            [
                'judul'    => 'Kegiatan Penertiban PKL di Kawasan Alun-Alun Kota',
                'kategori' => 'Operasi',
                'isi'      => '<p>Satuan Polisi Pamong Praja (Satpol PP) kembali melakukan penertiban terhadap Pedagang Kaki Lima (PKL) yang berjualan di bahu jalan kawasan Alun-Alun Kota pagi ini.</p><p>Penertiban berjalan kondusif dengan mengedepankan pendekatan persuasif kepada para pedagang agar mematuhi Peraturan Daerah tentang Ketertiban Umum.</p>',
            ],
            [
                'judul'    => 'Sosialisasi Peraturan Daerah Terbaru tentang Retribusi',
                'kategori' => 'Sosialisasi',
                'isi'      => '<p>Pemerintah Kota menggelar sosialisasi mengenai perubahan tarif retribusi pelayanan pasar. Sosialisasi ini bertujuan agar masyarakat dan pelaku usaha memahami dasar hukum dan mekanisme pembayaran terbaru.</p>',
            ]
        ];

        foreach ($beritaData as $item) {
            Berita::create([
                'judul'            => $item['judul'],
                'slug'             => Str::slug($item['judul']),
                'Kategori'         => $item['kategori'], // Kolom Baru
                'isi'              => $item['isi'],
                'path_gambar'      => null,
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
            ]);
        }

        // ==========================================
        // 2. DATA AGENDA
        // Schema: judul, deskripsi, lokasi, tanggal_kegiatan, waktu_mulai, waktu_selesai, tampilkan_publik, published_at
        // Note: Tidak ada slug & isi diubah jadi deskripsi
        // ==========================================
        $agendaData = [
            [
                'judul'            => 'Apel Besar Hari Ulang Tahun Satpol PP',
                'deskripsi'        => 'Seluruh anggota wajib hadir mengenakan Pakaian Dinas Upacara (PDU).',
                'lokasi'           => 'Halaman Kantor Walikota',
                'tanggal_kegiatan' => $now->copy()->addDays(3)->format('Y-m-d'),
                'waktu_mulai'      => '07:30:00',
                'waktu_selesai'    => '10:00:00',
            ],
            [
                'judul'            => 'Bimbingan Teknis Penggunaan Aplikasi E-Kinerja',
                'deskripsi'        => 'Peserta diharapkan membawa laptop masing-masing.',
                'lokasi'           => 'Aula Gedung B Lt. 2',
                'tanggal_kegiatan' => $now->copy()->addDays(7)->format('Y-m-d'),
                'waktu_mulai'      => '09:00:00',
                'waktu_selesai'    => '15:00:00',
            ],
            [
                'judul'            => 'Rapat Paripurna DPRD',
                'deskripsi'        => 'Agenda pembahasan RAPBD Tahun 2026.',
                'lokasi'           => 'Gedung DPRD',
                'tanggal_kegiatan' => $now->copy()->addDays(10)->format('Y-m-d'),
                'waktu_mulai'      => '13:00:00',
                'waktu_selesai'    => '16:00:00',
            ],
        ];

        foreach ($agendaData as $item) {
            Agenda::create([
                'judul'            => $item['judul'],
                // Slug dihapus
                'deskripsi'        => strip_tags($item['deskripsi']), // Membersihkan tag HTML untuk deskripsi singkat jika perlu, atau biarkan raw
                'lokasi'           => $item['lokasi'],
                'tanggal_kegiatan' => $item['tanggal_kegiatan'],
                'waktu_mulai'      => $item['waktu_mulai'],
                'waktu_selesai'    => $item['waktu_selesai'],
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
            ]);
        }

        // ==========================================
        // 3. DATA HIMBAUAN
        // Schema: judul, slug, isi, path_gambar, tampilkan_publik, published_at
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
            Himbauan::create([
                'judul'            => $item['judul'],
                'slug'             => Str::slug($item['judul']),
                'isi'              => $item['isi'],
                'path_gambar'      => null,
                'tampilkan_publik' => true,
                'published_at'     => $now,
                'created_by'       => $userId,
            ]);
        }
    }
}
