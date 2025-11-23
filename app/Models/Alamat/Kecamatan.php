<?php

namespace App\Models\Alamat;

use App\Models\Anggota\Anggota;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id', 'id');
    }

    public function desa()
    {
        return $this->hasMany(Desa::class, 'kecamatan_id', 'id');
    }

    public function anggota()
    {
        return $this->hasMany(Anggota::class, 'kecamatan_id', 'id');
    }

    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'kecamatan_id', 'id');
    }
}
