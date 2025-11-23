<?php

namespace App\Models\Alamat;

use App\Models\Anggota\Anggota;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $table = 'provinsi';

    public function anggota()
    {
        return $this->hasMany(Anggota::class, 'provinsi_id', 'id');
    }

    public function kabupaten()
    {
        return $this->hasMany(Kabupaten::class, 'provinsi_id', 'id');
    }

    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'provinsi_id', 'id');
    }
}
