<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class StatistikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create('id_ID');
        $userId = DB::table('users')->where('name','Komandan Regu')->value('id');

        // Ambil list ID Kecamatan & Desa yang sudah ada
        $kecamatanIds = DB::table('kecamatan')->pluck('id')->toArray();
        $desaIds = DB::table('desa')->pluck('id')->toArray();

        $anggotaIds = DB::table('anggota')->pluck('id')->toArray();


        // ==========================================
        // 3. SEED KATEGORI PENGADUAN
        // ==========================================
        $kategoriData = [
            ['nama' => 'Gangguan Ketenteraman', 'keterangan' => 'Keributan, miras, tawuran.'],
            ['nama' => 'Pelanggaran Perda', 'keterangan' => 'PKL liar, IMB.'],
            ['nama' => 'Bencana Alam', 'keterangan' => 'Banjir, longsor, pohon tumbang.'],
            ['nama' => 'Kebakaran', 'keterangan' => 'Kebakaran lahan/gedung.'],
        ];

        foreach ($kategoriData as $cat) {
            DB::table('kategori_pengaduan')->updateOrInsert(
                ['nama' => $cat['nama']], // Cek biar gak duplikat
                [
                    'keterangan' => $cat['keterangan'],
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $kategoriIds = DB::table('kategori_pengaduan')->pluck('id')->toArray();


        // ==========================================
        // 4. SEED PENGADUAN, LAMPIRAN & DISPOSISI
        // ==========================================
        $this->command->info('Sedang membuat 15 data dummy pengaduan...');

        for ($i = 0; $i < 15; $i++) {
            $status = $faker->randomElement(['diterima', 'diproses', 'selesai', 'ditolak']);
            $createdAt = $faker->dateTimeBetween('-1 month', 'now');

            // Logika waktu agar masuk akal
            $diterimaAt = $createdAt;
            $diprosesAt = in_array($status, ['diproses', 'selesai']) ? $faker->dateTimeBetween($createdAt, '+1 hour') : null;
            $selesaiAt  = ($status == 'selesai') ? $faker->dateTimeBetween($diprosesAt, '+2 days') : null;
            $ditolakAt  = ($status == 'ditolak') ? $faker->dateTimeBetween($createdAt, '+1 hour') : null;

            // A. Insert Pengaduan
            $pengaduanId = DB::table('pengaduan')->insertGetId([
                'nomor_tiket'    => 'TKT-' . date('Ymd') . '-' . $faker->unique()->numberBetween(1000, 9999),
                'nama_pelapor'   => $faker->name,
                'kontak_pelapor' => $faker->phoneNumber,
                'kategori_id'    => $faker->randomElement($kategoriIds),
                'deskripsi'      => $faker->paragraph,
                'lat'            => -6.200000 + ($faker->randomFloat(6, -0.05, 0.05)),
                'lng'            => 106.816666 + ($faker->randomFloat(6, -0.05, 0.05)),
                'kecamatan_id'   => $faker->randomElement($kecamatanIds),
                'desa_id'        => $faker->randomElement($desaIds),
                'lokasi'         => $faker->address,
                'status'         => $status,
                'catatan_tolak'  => ($status == 'ditolak') ? 'Laporan tidak valid.' : null,
                'diterima_at'    => $diterimaAt,
                'diproses_at'    => $diprosesAt,
                'selesai_at'     => $selesaiAt,
                'ditolak_at'     => $ditolakAt,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ]);

            // B. Insert Lampiran
            DB::table('pengaduan_lampiran')->insert([
                'pengaduan_id' => $pengaduanId,
                'path_file'    => 'uploads/dummy/bukti-' . $pengaduanId . '.jpg',
                'nama_file'    => 'bukti.jpg',
                'jenis'        => 'foto',
                'created_at'   => $createdAt,
            ]);

            // C. Insert Disposisi (Hanya jika status diproses/selesai)
            if (in_array($status, ['diproses', 'selesai'])) {
                DB::table('disposisi')->insert([
                    'pengaduan_id' => $pengaduanId,
                    'komandan_id'  => $userId,
                    'catatan'      => 'Tindak lanjuti segera.',
                    'batas_waktu'  => $faker->dateTimeBetween($diprosesAt, '+3 days'),
                    'status'       => ($status == 'selesai') ? 'selesai' : 'pending',
                    'created_by'   => $userId,
                    'created_at'   => $diprosesAt,
                    'updated_at'   => $diprosesAt,
                ]);
            }
        }


        // ==========================================
        // 5. SEED OPERASI & PENUGASAN
        // ==========================================
        $this->command->info('Sedang membuat data operasi...');

        // Ambil pengaduan yang sudah selesai/diproses untuk dijadikan operasi
        $targetPengaduan = DB::table('pengaduan')
            ->whereIn('status', ['diproses', 'selesai'])
            ->limit(5)
            ->get();

        foreach ($targetPengaduan as $p) {
            // A. Insert Operasi
            $operasiId = DB::table('operasi')->insertGetId([
                'kode_operasi'        => 'OPS-' . strtoupper($faker->bothify('??###')),
                'nomor_surat_tugas'   => 'ST/' . $faker->numberBetween(100, 999) . '/SATPOL/' . date('Y'),
                'surat_tugas_pdf'     => 'uploads/dummy/st.pdf',
                'tanggal_surat_tugas' => now(),
                'pengaduan_id'        => $p->id,
                'judul'               => 'Giat Penertiban Tiket ' . $p->nomor_tiket,
                'uraian'              => 'Melakukan pengecekan dan penertiban di lokasi.',
                'mulai'               => now(),
                'selesai'             => now()->addHours(4),
                'status'              => 'aktif',
                'created_by'          => $userId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // B. Insert Penugasan Anggota (Ambil 2 anggota acak)
            if (count($anggotaIds) > 0) {
                $tim = $faker->randomElements($anggotaIds, 2);
                foreach ($tim as $idx => $anggotaId) {
                    DB::table('operasi_penugasan')->insert([
                        'operasi_id' => $operasiId,
                        'anggota_id' => $anggotaId,
                        'peran'      => ($idx == 0) ? 'Ketua Tim' : 'Anggota',
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Seeding selesai!');
    }
}
