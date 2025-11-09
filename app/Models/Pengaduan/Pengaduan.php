<?php

namespace App\Models\Pengaduan;

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
        'alamat',
        'status',
    ];

    public function pengaduanLampiran()
    {
        return $this->hasMany(PengaduanLampiran::class, 'pengaduan_id');
    }

    public function kategoriPengaduan()
    {
        return $this->belongsTo(KategoriPengaduan::class, 'kategori_id');
    }
}
