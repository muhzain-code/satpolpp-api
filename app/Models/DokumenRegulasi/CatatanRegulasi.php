<?php

namespace App\Models\DokumenRegulasi;

use Illuminate\Database\Eloquent\Model;

class CatatanRegulasi extends Model
{
    protected $table = 'catatan_regulasi';

    protected $fillable = [
        'user_id',
        'regulasi_id',
        'halaman',
        'type',
        'data',
        'catatan',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function regulasi()
    {
        return $this->belongsTo(Regulasi::class);
    }
}
