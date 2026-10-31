<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kriteria extends Model
{
    protected $table = 'kriterias';

    protected $fillable = [
        'kode',
        'nama_kriteria',
        'tipe',
        'bobot',
    ];

    protected $casts = [
        'bobot' => 'float',
    ];

    public function penilaianCutis(): HasMany
    {
        return $this->hasMany(PenilaianCuti::class);
    }
}
