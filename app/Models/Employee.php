<?php

namespace App\Models;

use App\Models\Cuti;
use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- 1. IMPORT INI

class Employee extends Model
{
    use HasFactory, Notifiable; // <-- 2. TAMBAHKAN 'Notifiable' DI SINI

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
            // Jika user_id diisi/diubah
            if ($employee->user_id) {
                // Cari user tersebut
                $user = User::find($employee->user_id);
                // Jika user punya unit kerja, salin ID-nya ke employee
                if ($user && $user->unit_kerja_id) {
                    $employee->unit_kerja_id = $user->unit_kerja_id;
                }
            }
        });
    }
}