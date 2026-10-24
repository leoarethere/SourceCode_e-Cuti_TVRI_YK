<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Employee;
use App\Models\UnitKerja;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use SoftDeletes;

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
     * Menentukan apakah user boleh mengakses panel Filament.
     * Wajib diimplementasikan jika model menggunakan FilamentUser interface.
     * Tanpa method ini, semua user akan ditolak masuk di Filament v3.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [
            'admin',
            'kepala_stasiun',
            'tata_usaha',
            'ketua_tim',
            'pegawai',
        ]);
    }
    
    // Tambahkan di dalam fungsi booted() yang sudah ada
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            // LOGIKA 1: Update Ketua Unit — bersihkan unit lama agar tidak basi
            if ($user->role === 'ketua_tim' && $user->unit_kerja_id) {
                // Hapus penunjukan lama di unit lain
                UnitKerja::where('ketua_user_id', $user->id)
                    ->where('id', '!=', $user->unit_kerja_id)
                    ->update(['ketua_user_id' => null]);
                UnitKerja::where('id', $user->unit_kerja_id)
                    ->update(['ketua_user_id' => $user->id]);
            } else {
                // Bukan ketua_tim lagi: lepas semua penunjukan ketua atas user ini
                UnitKerja::where('ketua_user_id', $user->id)
                    ->update(['ketua_user_id' => null]);
            }
            
            // LOGIKA 2 (BARU): Sinkronisasi ke Employee
            // Jika user ini punya profil employee, update juga unit kerjanya
            if ($user->employee) {
                $user->employee->unit_kerja_id = $user->unit_kerja_id;
                $user->employee->saveQuietly(); // Simpan tanpa memicu event loop
            }
        });

        // Jika ketua dihapus (soft-delete), lepas penunjukan agar tidak menunjuk user sampah
        static::deleted(function (User $user) {
            UnitKerja::where('ketua_user_id', $user->id)->update(['ketua_user_id' => null]);
        });
    }
}