<?php

namespace App\Models\Humas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Himbauan extends Model
{
    use LogsActivity;
    protected $table = 'himbauan';

    protected $fillable = [
        'judul',
        'slug',
        'isi',
        'path_gambar',
        'tampilkan_publik',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('himbauan')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data himbauan berhasil " .
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
