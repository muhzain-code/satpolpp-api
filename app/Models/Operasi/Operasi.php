<?php

namespace App\Models\Operasi;

use App\Models\User;
use Spatie\Activitylog\LogOptions;
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
        'tanggal_surat_tugas',
        'surat_tugas_pdf',
        'pengaduan_id',
        'judul',
        'uraian',
        'mulai',
        'selesai',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('operasi')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data operasi berhasil " .
                    match ($event) {
                        'created' => 'ditambahkan',
                        'updated' => 'diperbarui',
                        'deleted' => 'dihapus',
                        default => $event,
                    } . ' oleh ' . (Auth::user()->name ?? 'Sistem') . '.'
            );
    }

    protected static function booted()
    {
        static::creating(fn($model) => $model->created_by ??= Auth::id());
        static::updating(fn($model) => $model->updated_by = Auth::id());
        static::deleting(fn($model) => $model->forceFill([
            'deleted_by' => Auth::id(),
        ])->saveQuietly());
    }
}
