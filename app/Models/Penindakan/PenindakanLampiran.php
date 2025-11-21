<?php

namespace App\Models\Penindakan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenindakanLampiran extends Model
{
    protected $table = 'penindakan_lampiran';

    protected $fillable = [
        'penindakan_id',
        'path_file',
        'nama_file',
        'jenis',
    ];

    protected $casts = [
        'jenis' => 'string',
    ];

    public function penindakan(): BelongsTo
    {
        return $this->belongsTo(Penindakan::class);
    }
}
