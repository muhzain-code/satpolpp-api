<?php

namespace App\Models\PPID;

use App\Models\Alamat\Desa;
use App\Models\Alamat\Kecamatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PPID extends Model
{
    use LogsActivity;
    protected $table = 'ppid_permohonan';

    protected $fillable = [
        'nomor_registrasi',
        'nama_pemohon',
        'no_ktp',
        'email',
        'kontak_pemohon',
        'informasi_diminta',
        'alasan_permintaan',
        'status',
        'jawaban_ppid',
        'file_jawaban',
        'ditangani_oleh',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id');
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'desa_id', 'id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ppid_permohonan')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data permohonan PPID berhasil " .
                    match ($event) {
                        'created' => 'ditambahkan',
                        'updated' => 'diperbarui',
                        'deleted' => 'dihapus',
                        default => $event,
                    } . ' oleh ' . (Auth::user()->name ?? 'Sistem') . '.'
            );
    }
}
