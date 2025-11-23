<?php

namespace App\Models\Pengaduan;

use App\Models\Alamat\Desa;
use App\Models\Alamat\Kabupaten;
use App\Models\Alamat\Kecamatan;
use App\Models\Alamat\Provinsi;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    protected $fillable = [
        'nomor_tiket',
        'nama_pelapor',
        'kontak_pelapor',
        'kategori_id',
        'deskripsi',
        'lat',
        'lng',
        'provinsi_id',
        'kabupaten_id',
        'kecamatan_id',
        'desa_id',
        'status',
        'diterima_at',
        'diproses_at',
        'selesai_at',
        'ditolak_at'
    ];

    public function pengaduanLampiran()
    {
        return $this->hasMany(PengaduanLampiran::class, 'pengaduan_id');
    }

    public function kategoriPengaduan()
    {
        return $this->belongsTo(KategoriPengaduan::class, 'kategori_id');
    }

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id', 'id');
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id');
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'desa_id', 'id');
    }
}
