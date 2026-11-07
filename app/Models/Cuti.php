<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cuti extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_MENUNGGU_ATASAN = 'Menunggu Persetujuan Atasan Langsung';
    public const STATUS_MENUNGGU_TU     = 'Menunggu Persetujuan Tata Usaha';
    public const STATUS_MENUNGGU_KEPALA = 'Menunggu Persetujuan Kepala';
    public const STATUS_DISETUJUI       = 'Disetujui';
    public const STATUS_DITOLAK         = 'Ditolak';

    public const STATUS_PENDING = [
        self::STATUS_MENUNGGU_ATASAN,
        self::STATUS_MENUNGGU_TU,
        self::STATUS_MENUNGGU_KEPALA,
    ];

    /**
     * Status yang memblokir tanggal (overlap): seluruh tahap pending + disetujui.
     */
    public const STATUS_PENDING_OR_APPROVED = [
        self::STATUS_MENUNGGU_ATASAN,
        self::STATUS_MENUNGGU_TU,
        self::STATUS_MENUNGGU_KEPALA,
        self::STATUS_DISETUJUI,
    ];

    /**
     * Susun status penolakan lengkap dengan penyebutnya.
     * Contoh: "Ditolak (oleh Ketua Tim Berita)".
     */
    public static function statusDitolak(string $oleh): string
    {
        return self::STATUS_DITOLAK . " (oleh {$oleh})";
    }

    /**
     * Cek apakah status mengandung penolakan.
     */
    public function isDitolak(): bool
    {
        return str_starts_with((string) $this->status_global, self::STATUS_DITOLAK);
    }

    protected $fillable = [
        'employee_id',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_akhir',
        'tanggal_cuti_array',
        'alasan',
        'urgensi',
        'periode',
        'alamat_selama_cuti',
        'lampiran_link',
        'lama_cuti',
        'skor_prioritas',
        'ranking',
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

    protected $casts = [
        'tanggal_cuti_array' => 'array',
        'tanggal_mulai'      => 'date',
        'tanggal_akhir'      => 'date',
        'urgensi'            => 'integer',
        'skor_prioritas'     => 'float',
        'ranking'            => 'integer',
    ];

    /**
     * Hitung lama cuti berdasarkan array tanggal.
     */
    public function getLamaCutiAttribute()
    {
        if (!empty($this->tanggal_cuti_array) && is_array($this->tanggal_cuti_array)) {
            return count($this->tanggal_cuti_array);
        }

        if ($this->tanggal_mulai && $this->tanggal_akhir) {
            return \Carbon\Carbon::parse($this->tanggal_mulai)
                ->diffInDays(\Carbon\Carbon::parse($this->tanggal_akhir)) + 1;
        }

        return 0;
    }

    /**
     * Format tanggal untuk tampilan.
     */
    public function getFormattedTanggalCutiAttribute()
    {
        if (!empty($this->tanggal_cuti_array) && is_array($this->tanggal_cuti_array)) {
            $dates = collect($this->tanggal_cuti_array)->sort()->values();

            if ($dates->count() <= 3) {
                return $dates->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y'))->join(', ');
            }

            $first = \Carbon\Carbon::parse($dates->first())->format('d/m/Y');
            $last = \Carbon\Carbon::parse($dates->last())->format('d/m/Y');

            return "$first ... $last ({$dates->count()} hari)";
        }

        return '-';
    }

    /**
     * Label rekomendasi SAW (visual saja, tidak mengubah status_global).
     */
    public function getStatusRekomendasiAttribute(): string
    {
        return match (true) {
            $this->ranking === null => 'Belum dihitung',
            $this->ranking === 1 => 'Prioritas Utama',
            $this->ranking === 2 => 'Direkomendasikan',
            $this->ranking === 3 => 'Pertimbangan',
            default              => 'Ditunda',
        };
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function penilaianCutis(): HasMany
    {
        return $this->hasMany(PenilaianCuti::class);
    }

    public function tataUsahaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tata_usaha_approver_id');
    }

    public function kepalaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_stasiun_approver_id');
    }

    public function atasanLangsungApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_langsung_approver_id');
    }
}
