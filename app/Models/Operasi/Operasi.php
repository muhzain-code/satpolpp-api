<?php

namespace App\Models\Operasi;

use App\Models\User;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operasi extends Model
{
    use SoftDeletes;

    protected $table = 'operasi';

    protected $fillable = [
        'kode_operasi',
        'nomor_surat_tugas',
        'pengaduan_id',
        'judul',
        'uraian',
        'mulai',
        'selesai',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'pengaduan_id' => 'integer',
        'mulai'        => 'datetime',
        'selesai'      => 'datetime',
        'created_by'   => 'integer',
        'updated_by'   => 'integer',
        'deleted_by'   => 'integer',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
