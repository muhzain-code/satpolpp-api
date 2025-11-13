<?php

namespace App\Models\DokumenRegulasi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Regulasi extends Model
{
    use LogsActivity;

    protected $table = 'regulasi';

    protected $fillable = [
        'kode',
        'judul',
        'tahun',
        'jenis',
        'ringkasan',
        'path_pdf',
        'aktif',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
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

    public function KemajuanPembacaan()
    {
        return $this->hasMany(KemajuanPembacaan::class, 'regulasi_id', 'id');
    }
    public function Penanda()
    {
        return $this->hasMany(Penanda::class, 'regulasi_id', 'id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('regulasi')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data regulasi berhasil " .
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
