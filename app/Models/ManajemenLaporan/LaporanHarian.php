<?php

namespace App\Models\ManajemenLaporan;

use App\Models\Anggota\Anggota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LaporanHarian extends Model
{
    use LogsActivity;
    protected $table = 'laporan_harian';

    protected $fillable = [
        'anggota_id',
        'jenis',
        'catatan',
        'lat',
        'lng',
        'status_validasi',
        'divalidasi_oleh',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class,'anggota_id', 'id');
    }
    public function lampiran()
    {
        return $this->hasMany(LaporanLampiran::class,'laporan_id','id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lampiran_harian')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data lampiran harian berhasil " .
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
        static::creating(fn($model) => $model->anggota_id ??= Auth::id());
    }
}
