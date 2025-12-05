<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('provinsi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_provinsi')->index();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('kabupaten', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kabupaten')->index();
            $table->foreignId('provinsi_id')->nullable()->constrained('provinsi')->nullOnDelete();
            $table->softDeletes();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kecamatan')->index();
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete();
            $table->softDeletes();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('desa', function (Blueprint $table) {
            $table->id();
            $table->string('nama_desa')->index();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->softDeletes();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        /**
         * ============================================================
         * 1. ANGGOTA & STRUKTUR ORGANISASI
         * ============================================================
         */
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // Contoh: Regu A, Regu B
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('anggota', function (Blueprint $table) {
            $table->id();

            $table->string('kode_anggota', 50)->unique();
            $table->string('nik', 16)->unique()->nullable();
            $table->string('nip', 18)->unique()->nullable();

            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();

            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->foreignId('provinsi_id')->nullable()->constrained('provinsi')->nullOnDelete();
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();

            $table->string('no_hp', 20)->nullable();

            $table->text('foto')->nullable();

            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('unit')->nullOnDelete();

            $table->enum('status', ['aktif', 'nonaktif', 'cuti', 'mutasi', 'pensiun', 'meninggal'])
                ->default('aktif');

            $table->enum('jenis_kepegawaian', ['asn', 'p3k', 'nonasn'])->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('anggota_id')
                ->nullable()
                ->constrained('anggota')
                ->nullOnDelete();
        });

        /**
         * ============================================================
         * 2. REGULASI (Referensi utama untuk banyak modul)
         * ============================================================
         */
        Schema::create('kategori_regulasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('regulasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 80)->unique();
            $table->string('judul');
            $table->smallInteger('tahun')->nullable();
            $table->foreignId('kategori_regulasi_id')->constrained('kategori_regulasi')->cascadeOnDelete();
            $table->text('ringkasan')->nullable();
            $table->string('path_pdf', 1000)->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('tampilkan_publik')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Untuk Penanda stabilo
        Schema::create('catatan_regulasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
            $table->integer('halaman');
            // Tipe: Hanya 'highlight' (visual/stabilo) dan 'note' (teks)
            $table->enum('type', ['highlight', 'note']);
            $table->json('data')->nullable(); // Koordinat & warna
            $table->string('warna')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('riwayat_baca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
            $table->boolean('status_selesai')->default(false);
            $table->integer('durasi_detik')->default(0);
            $table->date('tanggal');

            $table->timestamps();
        });

        Schema::create('statistik_pengguna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('streak_saat_ini')->default(0);
            $table->integer('rekor_streak')->default(0);
            $table->date('tanggal_aktivitas_terakhir')->nullable();
            $table->timestamps();
        });
        /**
         * ============================================================
         * 3. PENGADUAN PUBLIK
         * ============================================================
         */

        Schema::create('kategori_pengaduan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('keterangan')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 50)->unique();
            $table->string('nama_pelapor')->nullable();
            $table->string('kontak_pelapor')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_pengaduan')->nullOnDelete();
            $table->text('deskripsi')->nullable();

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desa')->nullOnDelete();

            $table->text('lokasi')->nullable();

            $table->enum('status', ['diterima', 'diproses', 'selesai', 'ditolak'])->default('diterima');
            $table->text('catatan_tolak')->nullable();

            $table->timestamp('diterima_at')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('ditolak_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pengaduan_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
            $table->string('path_file', 1000);
            $table->string('nama_file')->nullable();
            $table->enum('jenis', ['foto', 'video', 'dokumen'])->default('foto');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });


        /**
         * ============================================================
         * 4. DISPOSISI & OPERASI
         * ============================================================
         */

        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
            $table->foreignId('komandan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();

            $table->dateTime('batas_waktu')->nullable()->comment('Batas waktu SLA disposisi');
            $table->enum('status', ['pending', 'selesai'])->default('pending');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_operasi')->unique();
            $table->string('nomor_surat_tugas')->nullable()->unique();
            $table->string('surat_tugas_pdf')->nullable();
            $table->date('tanggal_surat_tugas')->nullable();
            $table->foreignId('pengaduan_id')->nullable()->constrained('pengaduan')->nullOnDelete();

            $table->string('judul');
            $table->text('uraian')->nullable();
            $table->dateTime('mulai')->nullable();
            $table->dateTime('selesai')->nullable();

            $table->enum('status', ['draft', 'aktif', 'selesai', 'batal'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('disposisi_id')->nullable()->constrained('disposisi')->cascadeOnDelete();
            $table->foreignId('operasi_id')->nullable()->constrained('operasi')->nullOnDelete();

            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('peran')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // 1️⃣ Unique kombinasi disposisi + anggota
            $table->unique(['disposisi_id', 'anggota_id'], 'uniq_disposisi_anggota');

            // 2️⃣ Unique kombinasi operasi + anggota
            $table->unique(['operasi_id', 'anggota_id'], 'uniq_operasi_anggota');
        });


        /**
         * ============================================================
         * 5. LAPORAN HARIAN
         * ============================================================
         */

        Schema::create('laporan_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->enum('jenis', ['aman', 'insiden'])->default('aman');
            $table->boolean('urgent')->default(false);
            $table->text('catatan')->nullable();

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->foreignId('kategori_pelanggaran_id')->nullable()->constrained('kategori_pengaduan')->nullOnDelete();
            $table->foreignId('regulasi_indikatif_id')->nullable()->constrained('regulasi')->nullOnDelete();

            $table->enum('severity', ['rendah', 'sedang', 'tinggi'])->default('rendah');
            $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->string('catatan_validasi')->nullable();
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('laporan_harian_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_harian')->cascadeOnDelete();
            $table->string('path_file', 1000);
            $table->string('nama_file')->nullable();
            $table->enum('jenis', ['foto', 'video', 'dokumen'])->default('foto');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /**
         * ============================================================
         * 6. PENINDAKAN & BAP
         * ============================================================
         */

        Schema::create('penindakan', function (Blueprint $table) {
            $table->id();

            // 1 dan hanya 1 sumber
            $table->foreignId('operasi_id')->nullable()->constrained('operasi')->nullOnDelete();
            $table->foreignId('laporan_harian_id')->nullable()->constrained('laporan_harian')->nullOnDelete();
            $table->foreignId('pengaduan_id')->nullable()->constrained('pengaduan')->nullOnDelete();

            $table->foreignId('anggota_pelapor_id')->nullable()->constrained('anggota')->nullOnDelete();

            // Jenis penindakan
            $table->enum('jenis_penindakan', ['teguran', 'pembinaan', 'penyitaan', 'proses_hukum'])->default('teguran');

            // Lokasi
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desa')->nullOnDelete();
            $table->text('lokasi')->nullable();
            $table->decimal('lat', 12, 8)->nullable();
            $table->decimal('lng', 12, 8)->nullable();

            $table->text('uraian')->nullable();

            $table->enum('status_validasi_komandan', ['menunggu', 'ditolak', 'revisi', 'disetujui'])
                ->default('menunggu')
                ->comment('Status approval awal oleh Komandan');

            $table->text('catatan_validasi_komandan')->nullable(); // Instruksi revisi atau alasan tolak
            $table->foreignId('komandan_validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_validasi_komandan')->nullable();

            // =====================================================================
            // TIER 2: VALIDASI PPNS (OPSIONAL)
            // =====================================================================
            // Flag ini HANYA diset oleh Komandan saat status 'disetujui'.
            // false = Kasus selesai di tempat (Teguran/Pembinaan).
            // true  = Kasus lanjut ke pemberkasan (PPNS).
            $table->boolean('butuh_validasi_ppns')->default(false);

            // Status ini baru terisi (menunggu) jika butuh_validasi_ppns = true.
            $table->enum('status_validasi_ppns', ['menunggu', 'ditolak', 'revisi', 'disetujui'])
                ->nullable()
                ->comment('Hanya aktif jika diteruskan oleh komandan');

            $table->text('catatan_validasi_ppns')->nullable();
            $table->foreignId('ppns_validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_validasi_ppns')->nullable();

            // --- AUDIT TRAIL ---
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // INDEXING UNTUK PERFORM
            $table->index(['operasi_id', 'pengaduan_id', 'laporan_harian_id']);
            $table->index(['status_validasi_komandan']); // Sering difilter di dashboard
            $table->index(['status_validasi_ppns']);
            $table->index(['butuh_validasi_ppns']);
        });

        Schema::create('penindakan_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penindakan_id')->constrained('penindakan')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('peran')->nullable();
            $table->timestamps();
            $table->unique(['penindakan_id', 'anggota_id']);
        });

        Schema::create('penindakan_regulasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penindakan_id')->constrained('penindakan')->cascadeOnDelete();
            $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
            $table->json('pasal_dilanggar')->nullable();
            $table->timestamps();
            $table->unique(['penindakan_id', 'regulasi_id']);
        });

        Schema::create('penindakan_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penindakan_id')->constrained('penindakan')->cascadeOnDelete();
            $table->string('path_file', 1000);
            $table->string('nama_file')->nullable();
            $table->enum('jenis', ['foto', 'video', 'dokumen'])->default('foto');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bap', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bap')->unique();
            $table->foreignId('penindakan_id')->unique()->constrained('penindakan')->cascadeOnDelete();
            $table->string('path_pdf', 1000)->nullable();
            $table->string('data_qr', 1000)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('nomor_bap');
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * ============================================================
         * 7. BUKU SAKU & PENANDA REGULASI
         * ============================================================
         */
        Schema::create('penanda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
            $table->string('pasal_atau_halaman')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'regulasi_id', 'pasal_atau_halaman']);
        });

        // Schema::create('kemajuan_pembacaan', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        //     $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
        //     $table->enum('status', ['belum', 'sedang', 'selesai'])->default('belum');
        //     $table->timestamp('terakhir_dibaca')->nullable();
        //     $table->timestamps();
        //     $table->unique(['user_id', 'regulasi_id']);
        // });

        Schema::create('buku_saku_progres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
            $table->smallInteger('bulan');
            $table->smallInteger('tahun');
            $table->enum('status', ['belum', 'sedang', 'selesai'])->default('belum');
            $table->timestamp('terakhir_dibaca')->nullable();
            $table->timestamps();
            $table->unique(
                ['user_id', 'regulasi_id', 'bulan', 'tahun'],
                'kemajuan_user_regulasi_bulanan_unique' // Nama custom agar mudah dibaca
            );
        });
        /**
         * ============================================================
         * 8. KONTEN HUMAS & GALERI
         * ============================================================
         */
        // Schema::create('konten', function (Blueprint $table) {
        //     $table->id();
        //     $table->enum('tipe', ['berita', 'agenda', 'himbauan'])->default('berita');
        //     $table->string('judul');
        //     $table->string('slug')->unique();
        //     $table->text('isi')->nullable();
        //     $table->string('path_gambar', 1000)->nullable();
        //     $table->boolean('tampilkan_publik')->default(true);
        //     $table->timestamp('published_at')->nullable();
        //     $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        //     $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        //     $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        //     $table->timestamps();
        //     $table->softDeletes();
        // });


        Schema::create('berita', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('Kategori'); //Operasi Pelatihan Dll
            $table->text('isi')->nullable();
            $table->string('path_gambar')->nullable();

            $table->boolean('tampilkan_publik')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agenda', function (Blueprint $table) {
            $table->id();

            $table->string('judul');
            $table->string('deskripsi')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('tanggal_kegiatan');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai')->nullable();
            $table->boolean('tampilkan_publik')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('himbauan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('isi')->nullable();
            $table->string('path_gambar')->nullable();

            $table->boolean('tampilkan_publik')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('galeri', function (Blueprint $table) {
            $table->id();
            $table->string('judul')->nullable();
            $table->string('path_file', 1000)->nullable();
            $table->enum('tipe', ['foto', 'video'])->default('foto');
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * ============================================================
         * 9. MODUL TAMBAHAN (PPID)
         * ============================================================
         */
        Schema::create('ppid_permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_registrasi')->unique();
            $table->string('nama_pemohon');
            $table->string('no_ktp')->nullable();
            $table->string('email')->nullable();
            $table->string('jenis_informasi')->nullable();
            $table->string('kontak_pemohon');
            $table->text('informasi_diminta');
            $table->text('alasan_permintaan');
            $table->text('alamat_lengkap');
            $table->enum('status', ['diajukan', 'diproses', 'dijawab', 'ditolak'])->default('diajukan');
            $table->enum('cara_memberikan', ['mengambil_langsung', 'kurir', 'pos', 'email', 'File']);
            $table->text('jawaban_ppid')->nullable();
            $table->string('file_jawaban')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tabel dalam urutan terbalik untuk menghindari masalah foreign key
        Schema::dropIfExists('ppid_permohonan');
        Schema::dropIfExists('galeri');
        Schema::dropIfExists('konten');
        Schema::dropIfExists('buku_saku_review_log');
        Schema::dropIfExists('buku_saku_progres');
        Schema::dropIfExists('penanda');
        Schema::dropIfExists('regulasi');
        Schema::dropIfExists('bap');
        Schema::dropIfExists('penindakan_lampiran');
        Schema::dropIfExists('penindakan_regulasi');
        Schema::dropIfExists('penindakan');
        Schema::dropIfExists('laporan_harian_lampiran');
        Schema::dropIfExists('laporan_harian');
        Schema::dropIfExists('operasi_penugasan');
        Schema::dropIfExists('operasi');
        Schema::dropIfExists('disposisi');
        Schema::dropIfExists('pengaduan_lampiran');
        Schema::dropIfExists('pengaduan');
        Schema::dropIfExists('kategori_pengaduan');
        Schema::dropIfExists('anggota');
        Schema::dropIfExists('unit');
        Schema::dropIfExists('jabatan');
    }
    // public function up(): void
    // {
    //     /**
    //      * ============================================================
    //      *  2. ANGGOTA & USER
    //      * ============================================================
    //      */
    //     Schema::create('jabatan', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('nama')->unique();
    //         $table->string('keterangan')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     Schema::create('unit', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('nama')->unique();
    //         $table->string('keterangan')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     Schema::create('anggota', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('kode_anggota', 50)->unique()->nullable();
    //         $table->string('nik', 32)->unique()->nullable();
    //         $table->string('nama');
    //         $table->enum('jenis_kelamin', ['l', 'p'])->nullable();
    //         $table->string('tempat_lahir')->nullable();
    //         $table->date('tanggal_lahir')->nullable();
    //         $table->string('alamat')->nullable();
    //         $table->string('foto')->nullable();
    //         $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
    //         $table->foreignId('unit_id')->nullable()->constrained('unit')->nullOnDelete();
    //         $table->enum('status', ['aktif', 'nonaktif', 'cuti'])->default('aktif');

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     Schema::table('users', function (Blueprint $table) {
    //         $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
    //     });

    //     /**
    //      * ============================================================
    //      *  3. PENGADUAN PUBLIK
    //      * ============================================================
    //      */
    //     Schema::create('kategori_pengaduan', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('nama')->unique();
    //         $table->string('keterangan')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     Schema::create('pengaduan', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('nama_pelapor')->nullable();
    //         $table->string('kontak_pelapor')->nullable();
    //         $table->foreignId('kategori_id')->nullable()->constrained('kategori_pengaduan')->nullOnDelete();
    //         $table->text('deskripsi')->nullable();
    //         $table->decimal('lat', 10, 7)->nullable();
    //         $table->decimal('lng', 10, 7)->nullable();
    //         $table->text('alamat')->nullable();
    //         $table->enum('status', ['baru', 'diproses', 'selesai'])->default('baru');
    //         $table->string('lampiran')->nullable();
    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     /**
    //      * ============================================================
    //      *  4. DISPOSISI
    //      * ============================================================
    //      */
    //     Schema::create('disposisi', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
    //         $table->foreignId('ke_anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
    //         $table->text('catatan')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     /**
    //      * ============================================================
    //      *  5. OPERASI & PENUGASAN
    //      * ============================================================
    //      */
    //     Schema::create('operasi', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('kode_operasi')->unique();
    //         $table->string('judul');
    //         $table->text('uraian')->nullable();
    //         $table->dateTime('mulai')->nullable();
    //         $table->dateTime('selesai')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     Schema::create('operasi_penugasan', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('operasi_id')->constrained('operasi')->cascadeOnDelete();
    //         $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
    //         $table->string('peran')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     /**
    //      * ============================================================
    //      *  6. PENINDAKAN & BAP
    //      * ============================================================
    //      */
    //     Schema::create('penindakan', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('operasi_id')->nullable()->constrained('operasi')->nullOnDelete();
    //         $table->foreignId('pengaduan_id')->nullable()->constrained('pengaduan')->nullOnDelete();
    //         $table->foreignId('anggota_pelapor_id')->nullable()->constrained('anggota')->nullOnDelete();
    //         $table->text('uraian')->nullable();
    //         $table->decimal('denda', 12, 2)->nullable();
    //         $table->timestamps();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->softDeletes();
    //     });

    //     Schema::create('penindakan_lampiran', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('penindakan_id')->constrained('penindakan')->cascadeOnDelete();
    //         $table->string('path_file', 1000);
    //         $table->string('nama_file')->nullable();
    //         $table->enum('jenis', ['foto', 'video'])->default('foto');
    //         $table->timestamps();
    //     });

    //     Schema::create('bap', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('nomor_bap')->unique();
    //         $table->foreignId('penindakan_id')->constrained('penindakan')->cascadeOnDelete();
    //         $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
    //         $table->string('path_pdf', 1000)->nullable();
    //         $table->string('data_qr', 1000)->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //     });

    //     /**
    //      * ============================================================
    //      *  7. REGULASI & BUKU SAKU
    //      * ============================================================
    //      */
    //     Schema::create('regulasi', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('kode', 80)->unique();
    //         $table->string('judul');
    //         $table->smallInteger('tahun')->nullable();
    //         $table->enum('jenis', ['perda', 'perkada', 'buku_saku'])->default('perda');
    //         $table->text('ringkasan')->nullable();
    //         $table->string('path_pdf', 1000)->nullable();
    //         $table->boolean('aktif')->default(true);

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     Schema::create('penanda', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    //         $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
    //         $table->text('catatan')->nullable();

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->unique(['user_id', 'regulasi_id']);
    //     });

    //     Schema::create('kemajuan_pembacaan', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    //         $table->foreignId('regulasi_id')->constrained('regulasi')->cascadeOnDelete();
    //         $table->enum('status', ['belum', 'sedang', 'selesai'])->default('belum');
    //         $table->timestamp('terakhir_dibaca')->nullable();
    //         $table->timestamps();
    //         $table->unique(['user_id', 'regulasi_id']);
    //     });

    //     /**
    //      * ============================================================
    //      *  8. LAPORAN HARIAN
    //      * ============================================================
    //      */
    //     Schema::create('laporan_harian', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
    //         $table->enum('jenis', ['aman', 'insiden'])->default('aman');
    //         $table->text('catatan')->nullable();
    //         $table->decimal('lat', 10, 7)->nullable();
    //         $table->decimal('lng', 10, 7)->nullable();
    //         $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
    //         $table->foreignId('divalidasi_oleh')->nullable()->constrained('anggota')->nullOnDelete();
    //         $table->timestamps();
    //     });

    //     Schema::create('laporan_harian_lampiran', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('laporan_id')->constrained('laporan_harian')->cascadeOnDelete();
    //         $table->string('path_file', 1000);
    //         $table->string('nama_file')->nullable();
    //         $table->enum('jenis', ['foto', 'video'])->default('foto');
    //         $table->timestamps();
    //     });

    //     /**
    //      * ============================================================
    //      *  9. KONTEN HUMAS & GALERI
    //      * ============================================================
    //      */
    //     Schema::create('konten', function (Blueprint $table) {
    //         $table->id();
    //         $table->enum('tipe', ['berita', 'agenda', 'himbauan'])->default('berita');
    //         $table->string('judul');
    //         $table->text('isi')->nullable();
    //         $table->string('path_gambar', 1000)->nullable();
    //         $table->boolean('tampilkan_publik')->default(true);

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     Schema::create('galeri', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('judul')->nullable();
    //         $table->string('path_file', 1000)->nullable();
    //         $table->enum('tipe', ['foto', 'video'])->default('foto');

    //         $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    //         $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    //         $table->timestamps();
    //         $table->softDeletes();
    //     });
    // }

    // public function down(): void
    // {
    //     // Schema::dropIfExists('log_audit');
    //     Schema::dropIfExists('galeri');
    //     Schema::dropIfExists('konten');
    //     Schema::dropIfExists('laporan_harian_lampiran');
    //     Schema::dropIfExists('laporan_harian');
    //     Schema::dropIfExists('kemajuan_pembacaan');
    //     Schema::dropIfExists('penanda');
    //     Schema::dropIfExists('regulasi');
    //     Schema::dropIfExists('bap');
    //     Schema::dropIfExists('penindakan_lampiran');
    //     Schema::dropIfExists('penindakan');
    //     Schema::dropIfExists('operasi_penugasan');
    //     Schema::dropIfExists('operasi');
    //     Schema::dropIfExists('disposisi');
    //     Schema::dropIfExists('pengaduan_lampiran');
    //     Schema::dropIfExists('pengaduan');
    //     Schema::dropIfExists('kategori_pengaduan');

    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropConstrainedForeignId('anggota_id');
    //     });

    //     Schema::dropIfExists('anggota');
    //     Schema::dropIfExists('unit');
    //     Schema::dropIfExists('jabatan');
    // }
};
