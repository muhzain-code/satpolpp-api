<?php

namespace App\Models\Alamat;

use App\Models\Anggota\Anggota;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Database\Eloquent\Model;

class Kabupaten extends Model
{
    protected $table = 'kabupaten';

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id');
    }

    public function kecamatan()
    {
        return $this->hasMany(Kecamatan::class, 'kabupaten_id', 'id');
    }

    public function anggota()
    {
        return $this->hasMany(Anggota::class, 'kabupaten_id', 'id');
    }

    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'kabupaten_id', 'id');
    }
}
