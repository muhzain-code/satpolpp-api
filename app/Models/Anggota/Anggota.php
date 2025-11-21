<?php

namespace App\Models\Anggota;

use App\Models\User;
use App\Models\Anggota\Jabatan;
use App\Models\ManajemenLaporan\LaporanHarian;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Anggota extends Model
{
    use LogsActivity;

    protected $table = 'anggota';
    protected $fillable = [
        'kode_anggota',
        'nik',
        'nip',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'foto',
        'jabatan_id',
        'unit_id',
        'status',
        'jenis_kepegawaian',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
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

    public function LaporanHarian()
    {
        return $this->hasMany(LaporanHarian::class, 'anggota_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class,'anggota_id','id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('anggota')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn($event) =>
                "Data anggota berhasil " .
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
