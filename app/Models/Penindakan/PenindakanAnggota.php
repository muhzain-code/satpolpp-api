<?php

namespace App\Models\Penindakan;

use App\Models\Anggota\Anggota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PenindakanAnggota extends Model
{
    use HasFactory;

    protected $table = 'penindakan_anggota';

    // Sesuaikan dengan kolom allowed input
    protected $fillable = [
        'penindakan_id',
        'anggota_id',
        'peran',
    ];

    /**
     * Relasi ke penindakan
     */
    public function penindakan()
    {
        return $this->belongsTo(Penindakan::class);
    }

    /**
     * Relasi ke anggota
     */
    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    /**
     * Optional helper:
     * Cek apakah anggota ini adalah PIC
     * (PIC tersimpan di tabel penindakan: pic_anggota_id)
     */
    public function getIsPicAttribute()
    {
        return $this->penindakan && 
               $this->penindakan->pic_anggota_id == $this->anggota_id;
    }
}
