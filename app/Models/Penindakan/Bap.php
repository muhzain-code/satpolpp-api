<?php

namespace App\Models\Penindakan;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bap extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bap';

    protected $fillable = [
        'nomor_bap',
        'penindakan_id',
        'path_pdf',
        'data_qr',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function penindakan()
    {
        return $this->belongsTo(Penindakan::class, 'penindakan_id');
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
