<?php

namespace App\Models\DokumenRegulasi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KemajuanPembacaan extends Model
{
    use LogsActivity;

    protected $table = 'buku_saku_progres';

    protected $fillable = [
        'user_id',
        'regulasi_id',
        'bulan',
        'tahun',
        'status',
        'terakhir_dibaca',
    ];

    public function Regulasi()
    {
        return $this->belongsTo(Regulasi::class,'regulasi_id','id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('buku_saku_progres')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data kemajuan pembacaan berhasil " .
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
        static::creating(fn($model) => $model->user_id ??= Auth::id());
    }
}
