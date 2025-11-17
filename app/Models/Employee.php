<?php

namespace App\Models;

use App\Models\Cuti;
use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- 1. IMPORT INI
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory, Notifiable, SoftDeletes; // ✅ Tambahkan ini

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // <-- INI YANG HILANG/TERLEWAT
        'nama',
        'email',
        'telp',
        'alamat_domisili',
        'NIP',
        'jabatan',
        'unit_kerja_id',
        'tanggal_bergabung',
        'sisa_cuti_tahunan',
    ];

    /**
     * Mendefinisikan relasi: Satu Employee memiliki banyak Cuti.
     */
    public function cutis(): HasMany
    {
        return $this->hasMany(Cuti::class);
    }

    /**
     * Mendefinisikan relasi: Satu Employee dimiliki oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($employee) {
            // Hanya sinkronisasi jika user_id diubah
            if ($employee->isDirty('user_id') && $employee->user_id) {
                $user = User::find($employee->user_id);
                if ($user && $user->unit_kerja_id) {
                    $employee->unit_kerja_id = $user->unit_kerja_id;
                }
            }
        });
    }
}