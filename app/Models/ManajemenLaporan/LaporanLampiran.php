<?php

namespace App\Models\ManajemenLaporan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LaporanLampiran extends Model
{
    use LogsActivity;
    protected $table = 'laporan_harian_lampiran';

    protected $fillable = [
        'laporan_id',
        'path_file',
        'nama_file',
        'jenis',
    ];

    public function LaporanHarian()
    {
        return $this->belongsTo(LaporanHarian::class,'laporan_id','id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('laporan_harian_lampiran')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data lampiran laporan harian berhasil " .
                    match ($event) {
                        'created' => 'ditambahkan',
                        'updated' => 'diperbarui',
                        'deleted' => 'dihapus',
                        default => $event,
                    } . ' oleh ' . (Auth::user()->name ?? 'Sistem') . '.'
            );
    }
}
