<?php

namespace App\Models\Operasi;

use App\Models\User;
use App\Models\Anggota\Unit;
use App\Models\Anggota\Anggota;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disposisi extends Model
{
    use SoftDeletes;

    protected $table = 'disposisi';

    protected $fillable = [
        'pengaduan_id',
        'ke_unit_id',
        'catatan',
        'batas_waktu',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'pengaduan_id'   => 'integer',
        'ke_unit_id'     => 'integer',
        'created_by'     => 'integer',
        'updated_by'     => 'integer',
        'deleted_by'     => 'integer',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function keUnit()
    {
        return $this->belongsTo(Unit::class, 'ke_unit_id');
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
