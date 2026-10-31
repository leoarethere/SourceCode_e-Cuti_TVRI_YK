<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianCuti extends Model
{
    protected $table = 'penilaian_cutis';

    protected $fillable = [
        'cuti_id',
        'kriteria_id',
        'nilai',
    ];

    protected $casts = [
        'nilai' => 'float',
    ];

    public function cuti(): BelongsTo
    {
        return $this->belongsTo(Cuti::class);
    }

    public function kriteria(): BelongsTo
    {
        return $this->belongsTo(Kriteria::class);
    }
}
