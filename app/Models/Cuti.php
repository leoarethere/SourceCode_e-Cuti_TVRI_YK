<?php

namespace App\Models;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cuti extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_akhir',
        'tanggal_cuti_array', // ✅ TAMBAHAN BARU
        'alasan',
        'alamat_selama_cuti',
        'lampiran_link',
        'status_atasan_langsung',
        'tanggapan_atasan_langsung',
        'status_tata_usaha',
        'tanggapan_tata_usaha',
        'status_kepala',
        'tanggapan_kepala',
        'status_global',
        'atasan_langsung_approver_id',
        'tata_usaha_approver_id',
        'kepala_stasiun_approver_id',
    ];

    // ✅ TAMBAHAN: Cast JSON ke array
    protected $casts = [
        'tanggal_cuti_array' => 'array',
    ];

    /**
     * ✅ HELPER METHOD: Hitung lama cuti berdasarkan array tanggal
     */
    public function getLamaCutiAttribute()
    {
        // Jika ada tanggal_cuti_array, hitung dari array
        if (!empty($this->tanggal_cuti_array) && is_array($this->tanggal_cuti_array)) {
            return count($this->tanggal_cuti_array);
        }
        
        // Fallback ke perhitungan lama (range tanggal)
        if ($this->tanggal_mulai && $this->tanggal_akhir) {
            return \Carbon\Carbon::parse($this->tanggal_mulai)
                ->diffInDays(\Carbon\Carbon::parse($this->tanggal_akhir)) + 1;
        }
        
        return 0;
    }

    /**
     * ✅ HELPER METHOD: Format tanggal untuk tampilan
     */
    public function getFormattedTanggalCutiAttribute()
    {
        if (!empty($this->tanggal_cuti_array) && is_array($this->tanggal_cuti_array)) {
            $dates = collect($this->tanggal_cuti_array)->sort()->values();
            
            if ($dates->count() <= 3) {
                // Jika sedikit, tampilkan semua
                return $dates->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m/Y'))->join(', ');
            }
            
            // Jika banyak, tampilkan first, ..., last
            $first = \Carbon\Carbon::parse($dates->first())->format('d/m/Y');
            $last = \Carbon\Carbon::parse($dates->last())->format('d/m/Y');
            return "$first ... $last ({$dates->count()} hari)";
        }
        
        return '-';
    }

    /**
     * Relasi ke Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tataUsahaApprover()
    {
        return $this->belongsTo(User::class, 'tata_usaha_approver_id');
    }

    public function kepalaApprover()
    {
        return $this->belongsTo(User::class, 'kepala_stasiun_approver_id');
    }
    
    public function atasanLangsungApprover()
    {
        return $this->belongsTo(User::class, 'atasan_langsung_approver_id');
    }
}