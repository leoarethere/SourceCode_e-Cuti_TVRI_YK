<?php

namespace App\Models;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- Import ini
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cuti extends Model
{
    use HasFactory, SoftDeletes;

    // Tambahkan 'status' agar bisa diisi
    protected $fillable = [
        'employee_id', // <-- INI YANG HILANG/TERLEWAT
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_akhir',
        'alasan',
        'alamat_selama_cuti',
        'lampiran_link', // <-- TAMBAHKAN INI
        'status_atasan_langsung',
        'tanggapan_atasan_langsung',
        'status_tata_usaha',
        'tanggapan_tata_usaha',
        'status_kepala',
        'tanggapan_kepala',
        'status_global',

        // === TAMBAHKAN 3 BARIS INI ===
        'atasan_langsung_approver_id',
        'tata_usaha_approver_id',
        'kepala_stasiun_approver_id',
        // =============================
    ];

    /**
     * Mendefinisikan relasi: Satu Cuti dimiliki oleh satu Employee.
     */
    public function employee(): BelongsTo // <-- Tipe relasinya
    {
        return $this->belongsTo(Employee::class);
    }

    public function sdmApprover()
    {
        return $this->belongsTo(User::class, 'sdm_approver_id');
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