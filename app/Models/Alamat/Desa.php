<?php

namespace App\Models\Alamat;

use App\Models\Anggota\Anggota;
use App\Models\Pengaduan\Pengaduan;
use App\Models\PPID\PPID;
use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    protected $table = 'desa';

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id');
    }

    public function anggota()
    {
        return $this->hasMany(Anggota::class, 'desa_id', 'id');
    }
    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'desa_id', 'id');
    }
    public function ppid()
    {
        return $this->hasMany(PPID::class, 'desa_id', 'id');
    }
}
