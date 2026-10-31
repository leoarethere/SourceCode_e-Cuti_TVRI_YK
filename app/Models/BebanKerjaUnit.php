<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BebanKerjaUnit extends Model
{
    protected $table = 'beban_kerja_units';

    protected $fillable = [
        'unit_kerja_id',
        'periode',
        'nilai',
    ];

    protected $casts = [
        'nilai' => 'float',
    ];

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
