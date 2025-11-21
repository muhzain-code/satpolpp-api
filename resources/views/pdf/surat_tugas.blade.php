<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        /* KOP */
        .kop-container {
            text-align: center;
            margin-bottom: 6px;
        }

        .kop-title {
            font-size: 16px;
            font-weight: bold;
        }

        .kop-subtitle {
            font-size: 12px;
        }

        .line-1 {
            border-top: 3px solid #000;
            margin-top: 4px;
        }

        .line-2 {
            border-top: 1px solid #000;
            margin-top: 2px;
            margin-bottom: 20px;
        }

        /* Bagian Judul */
        h3 {
            text-align: center;
            margin-bottom: 0;
            text-decoration: underline;
            font-size: 15px;
        }

        /* Table Tugas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 16px;
        }

        /* TTD */
        .ttd {
            width: 100%;
            margin-top: 40px;
        }

        .ttd td {
            border: none;
            text-align: right;
            padding-right: 40px;
        }
    </style>
</head>

<body>

    <!-- KOP SURAT -->
    <div class="kop-container">
        <div class="kop-title">PEMERINTAH KABUPATEN/KOTA {{ strtoupper($instansi ?? '________') }}</div>
        <div class="kop-title">SATUAN POLISI PAMONG PRAJA</div>
        <div class="kop-subtitle">Alamat: {{ $alamat_instansi ?? '_________________________________________' }}</div>
    </div>

    <div class="line-1"></div>
    <div class="line-2"></div>

    <!-- JUDUL -->
    <h3>SURAT TUGAS</h3>
    <p style="text-align:center; margin-top:4px;">
        Nomor: <strong>{{ $operasi->nomor_surat_tugas }}</strong>
    </p>

    <!-- DASAR -->
    <p class="section-title">I. DASAR</p>
    <ol style="margin-top:4px;">
        <li>Peraturan Daerah tentang Penyelenggaraan Ketertiban Umum dan Ketentraman Masyarakat.</li>
        <li>Perintah Kepala Satuan Polisi Pamong Praja Kabupaten/Kota {{ $instansi ?? '________' }}.</li>
        @if ($operasi->jenis_operasi == 'pengaduan' && $operasi->pengaduan)
            <li>Pengaduan masyarakat Nomor Tiket: {{ $operasi->pengaduan->nomor_tiket }}.</li>
        @endif
    </ol>

    <!-- TUGAS -->
    <p class="section-title">II. TUGAS</p>
    <p>Melaksanakan kegiatan/operasi: <strong>{{ $operasi->judul }}</strong></p>

    @if ($operasi->uraian)
        <p><strong>Uraian Tugas:</strong> {{ $operasi->uraian }}</p>
    @endif

    <p>
        <strong>Waktu Pelaksanaan:</strong>
        {{ \Carbon\Carbon::parse($operasi->mulai)->translatedFormat('d F Y H:i') }}
        s/d
        {{ \Carbon\Carbon::parse($operasi->selesai)->translatedFormat('d F Y H:i') }}
    </p>

    <!-- PERSONAL -->
    <p class="section-title">III. PERSONEL YANG DITUGASKAN</p>

    <table>
        <thead>
            <tr>
                <th style="width:40px; text-align:center;">No</th>
                <th>Nama</th>
                <th>Jabatan / Peran</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($penugasan as $i => $p)
                <tr>
                    <td style="text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ $p->anggota->nama }}</td>
                    <td>{{ $p->peran ?? $p->anggota->jabatan?->nama }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PENUTUP -->
    <table class="ttd">
        <tr>
            <td>
                {{ $tanggal }} <br>
                <strong>Kepala Satuan Polisi Pamong Praja</strong><br><br><br><br>
                <strong>{{ $operasi->createdBy?->anggota?->nama ?? '(Nama Pejabat)' }}</strong><br>
                Pangkat/Gol: {{ $operasi->createdBy?->anggota?->jabatan?->nama ?? '-' }} <br>
                NIP: {{ $operasi->createdBy?->anggota?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>

</html>
