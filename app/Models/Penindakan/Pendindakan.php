<?php

namespace App\Models\Penindakan;

use App\Models\User;
use App\Models\Anggota\Anggota;
use App\Models\Operasi\Operasi;
use App\Models\Pengaduan\Pengaduan;
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
        'anggota_pelapor_id',
        'uraian',
        'denda',
        'status_validasi_ppns',
        'catatan_validasi_ppns',
        'ppns_validator_id',
        'tanggal_validasi_ppns',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'denda' => 'decimal:2',
    ];

    public function operasi()
    {
        return $this->belongsTo(Operasi::class);
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
}
