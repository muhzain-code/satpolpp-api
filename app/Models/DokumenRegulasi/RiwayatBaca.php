<?php

namespace App\Models\DokumenRegulasi;

use Illuminate\Database\Eloquent\Model;

class RiwayatBaca extends Model
{
    protected $table = 'riwayat_baca';

    protected $fillable = [
        'user_id',
        'regulasi_id',
        'status_selesai',
        'durasi_detik',
        'tanggal',
    ];

    public function regulasi()
    {
        return $this->belongsTo(Regulasi::class);
    }
}
