<?php

namespace App\Models\Penindakan;

use App\Models\User;
use App\Models\Alamat\Desa;
use App\Models\Anggota\Anggota;
use App\Models\Operasi\Operasi;
use App\Models\Alamat\Kecamatan;
use Spatie\Activitylog\LogOptions;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ManajemenLaporan\LaporanHarian;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penindakan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'penindakan';

    protected $fillable = [
        'operasi_id',
        'laporan_harian_id',
        'pengaduan_id',
        'jenis_penindakan',
        'anggota_pelapor_id',
        'kecamatan_id',
        'desa_id',
        'lokasi',
        'lat',
        'lng',
        'uraian',
        'status_validasi_komandan',
        'catatan_validasi_komandan',
        'komandan_validator_id',
        'tanggal_validasi_komandan',
        'butuh_validasi_ppns',
        'status_validasi_ppns',
        'catatan_validasi_ppns',
        'ppns_validator_id',
        'tanggal_validasi_ppns',
        'created_by',
        'updated_by',
        'deleted_by',
    ];



    public function operasi()
    {
        return $this->belongsTo(Operasi::class);
    }
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
    public function penindakanLampiran()
    {
        return $this->hasMany(PenindakanLampiran::class);
    }
    public function penindakanRegulasi()
    {
        return $this->hasMany(PenindakanRegulasi::class);
    }

    public function penindakanAnggota()
    {
        return $this->hasMany(PenindakanAnggota::class);
    }

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function laporanHarian()
    {
        return $this->belongsTo(LaporanHarian::class, 'laporan_harian_id');
    }

    public function anggotaPelapor()
    {
        return $this->belongsTo(Anggota::class, 'anggota_pelapor_id');
    }

    public function ppnsValidator()
    {
        return $this->belongsTo(Anggota::class, 'ppns_validator_id');
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('penindakan')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data penindakan berhasil " .
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
