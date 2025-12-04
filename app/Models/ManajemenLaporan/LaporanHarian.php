<?php

namespace App\Models\ManajemenLaporan;

use App\Models\Anggota\Anggota;
use App\Models\DokumenRegulasi\Regulasi;
use App\Models\Pengaduan\KategoriPengaduan;
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
        'kecamatan_id',
        'desa_id',
        'lokasi',
        'lat',
        'lng',
        'kategori_pelanggaran_id',
        'regulasi_indikatif_id',
        'severity',
        'telah_dieskalasi',
        'status_validasi',
        'catatan_validasi',
        'divalidasi_oleh',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class, 'anggota_id', 'id');
    }
    public function lampiran()
    {
        return $this->hasMany(LaporanLampiran::class, 'laporan_id', 'id');
    }
    public function kategoriPelanggaran()
    {
        return $this->belongsTo(KategoriPengaduan::class, 'kategori_pelanggaran_id', 'id');
    }
    public function regulasi()
    {
        return $this->belongsTo(Regulasi::class, 'regulasi_indikatif_id', 'id');
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
    public function validator()
    {
        return $this->belongsTo(Anggota::class, 'divalidasi_oleh');
    }
}
