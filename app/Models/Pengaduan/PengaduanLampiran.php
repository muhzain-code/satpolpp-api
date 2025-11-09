<?php

namespace App\Models\Pengaduan;

use Illuminate\Database\Eloquent\Model;

class PengaduanLampiran extends Model
{
    protected $table = 'pengaduan_lampiran';

    protected $fillable = [
        'pengaduan_id',
        'path_file',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }
}
