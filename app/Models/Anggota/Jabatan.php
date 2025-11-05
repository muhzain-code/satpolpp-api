<?php

namespace App\Models\Anggota;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama',
        'keterangan',
    ];
}
