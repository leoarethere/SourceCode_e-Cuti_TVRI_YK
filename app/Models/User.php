<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Employee;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable; // <-- TAMBAHKAN INI
use Illuminate\Database\Eloquent\Relations\HasOne; // <-- TAMBAHKAN INI
use Illuminate\Foundation\Auth\User as Authenticatable; // Pastikan ini ada jika belum
use Illuminate\Database\Eloquent\Factories\HasFactory; // Import model UnitKerja

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'signature_image_path',
        'unit_kerja_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
    
    /**
     * "The Magic Logic"
     * Fungsi ini jalan otomatis setiap kali User disimpan (Create/Update)
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            // Cek 1: Apakah dia Ketua Tim?
            // Cek 2: Apakah dia dipilihkan Unit Kerja?
            if ($user->role === 'ketua_tim' && $user->unit_kerja_id) {
                
                // CARA KERJA:
                // Cari Unit Kerja yang dipilih, lalu update kolom 'ketua_user_id' 
                // dengan ID user ini.
                UnitKerja::where('id', $user->unit_kerja_id)
                    ->update(['ketua_user_id' => $user->id]);
            }
        });
    }
}