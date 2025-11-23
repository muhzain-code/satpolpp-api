<?php

namespace App\Models\Penindakan;

use Illuminate\Database\Eloquent\Model;
use App\Models\DokumenRegulasi\Regulasi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenindakanRegulasi extends Model
{
    protected $table = 'penindakan_regulasi';

    protected $fillable = [
        'penindakan_id',
        'regulasi_id',
        'pasal_dilanggar',
    ];

    protected $casts = [
        'pasal_dilanggar' => 'array',
    ];

    public function penindakan(): BelongsTo
    {
        return $this->belongsTo(Penindakan::class);
    }

    public function regulasi(): BelongsTo
    {
        return $this->belongsTo(Regulasi::class);
    }
}
