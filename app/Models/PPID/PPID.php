<?php

namespace App\Models\PPID;

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
       return $this->belongsTo(User::class,'ditangani_oleh');
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
