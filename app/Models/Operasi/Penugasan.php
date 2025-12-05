<?php

namespace App\Models\Operasi;

use App\Models\User;
use App\Models\Anggota\Anggota;
use App\Models\Operasi\Operasi;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penugasan extends Model
{
    use SoftDeletes;

    protected $table = 'penugasan';

    protected $fillable = [
        'pengaduan_id',
        'operasi_id',
        'anggota_id',
        'peran',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    public function operasi()
    {
        return $this->belongsTo(Operasi::class);
    }
    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function operasiActive()
    {
        return $this->belongsTo(Operasi::class)->where('status', 'aktif');
    }   

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
