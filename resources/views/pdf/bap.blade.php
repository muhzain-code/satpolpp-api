<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .judul {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
        }

        .section {
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="judul">BERITA ACARA PENINDAKAN</div>
        <div>Nomor: {{ $nomor_bap }}</div>
    </div>

    <div class="section">
        <table>
            <tr>
                <td width="30%">Tanggal</td>
                <td>: {{ now()->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td>Nomor Penindakan</td>
                <td>: {{ $penindakan->nomor_penindakan }}</td>
            </tr>
            <tr>
                <td>Regulasi</td>
                <td>: {{ $penindakan->regulasi->nama ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <b>Uraian Penindakan:</b><br>
        {!! nl2br(e($penindakan->uraian)) !!}
    </div>

    <div class="section">
        <b>QR Code Validasi:</b><br><br>
        <img src="data:image/png;base64,{{ $qr_base64 }}" width="120">
    </div>

</body>

</html>
