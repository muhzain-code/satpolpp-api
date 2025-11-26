<?php

namespace App\Models\DokumenRegulasi;

use Illuminate\Database\Eloquent\Model;

class StatistikPengguna extends Model
{
    protected $table = 'statistik_pengguna';

    protected $fillable = [
        'user_id',
        'rekor_streak',
        'streak_saat_ini',
        'tanggal_aktivitas_terakhir',
    ];
}
