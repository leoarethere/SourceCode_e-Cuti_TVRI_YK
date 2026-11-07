# Diagram Basis Data e-Cuti

ERD berikut disusun dari **skema database aktif** (diekspor langsung dari MySQL, termasuk seluruh riwayat migrasi). Diagram dirender dengan [Mermaid](https://mermaid.js.org/) — di GitHub cukup buka file ini untuk melihat gambar ERD.

## ERD

```mermaid
erDiagram
    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar role "admin, kepala_stasiun, tata_usaha, ketua_tim, pegawai (default: pegawai)"
        bigint unit_kerja_id FK "(ke unit_kerjas)"
        timestamp email_verified_at
        varchar password
        varchar signature_image_path
        varchar remember_token
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    EMPLOYEES {
        bigint id PK
        bigint user_id FK "(ke users, nullable)"
        varchar nama
        varchar email
        varchar telp
        text alamat_domisili
        varchar NIP UK
        varchar jabatan
        decimal nilai_kinerja "C2 SAW, default 75"
        bigint unit_kerja_id FK "(ke unit_kerjas)"
        date tanggal_bergabung "C3 SAW"
        int sisa_cuti_tahunan "default 12"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    UNIT_KERJAS {
        bigint id PK
        varchar nama
        bigint ketua_user_id FK "(ke users, nullable)"
        timestamp created_at
        timestamp updated_at
    }

    CUTIS {
        bigint id PK
        bigint employee_id FK "(ke employees)"
        json tanggal_cuti_array "daftar tanggal terpilih (acak boleh)"
        date tanggal_mulai "min(tanggal_cuti_array)"
        date tanggal_akhir "max(tanggal_cuti_array)"
        char periode "YYYY-MM, indexed"
        int lama_cuti "jumlah hari"
        json detail_tanggal "LEGACY: tak dipakai kode"
        varchar jenis_cuti
        text alasan
        tinyint urgensi "C4 SAW, 1-5, nullable"
        text alamat_selama_cuti
        varchar lampiran_link
        enum status_atasan_langsung "pending, approved, rejected"
        text tanggapan_atasan_langsung
        bigint atasan_langsung_approver_id FK "(ke users)"
        enum status_tata_usaha "pending, approved, rejected"
        text tanggapan_tata_usaha
        bigint tata_usaha_approver_id FK "(ke users)"
        enum status_kepala "pending, approved, rejected"
        text tanggapan_kepala
        bigint kepala_stasiun_approver_id FK "(ke users)"
        varchar status_global "alur 3 tahap + Ditolak (oleh ...)"
        decimal skor_prioritas "C? skor SAW, 8.6"
        int ranking "hasil SAW"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    KRITERIAS {
        bigint id PK
        varchar kode UK "C1..C5"
        varchar nama_kriteria
        enum tipe "benefit, cost"
        decimal bobot "total = 1.0000"
        timestamp created_at
        timestamp updated_at
    }

    BEBAN_KERJA_UNITS {
        bigint id PK
        bigint unit_kerja_id FK "(ke unit_kerjas)"
        char periode "YYYY-MM"
        decimal nilai "0-100, C5 SAW (cost)"
        timestamp created_at
        timestamp updated_at
    }

    PENILAIAN_CUTIS {
        bigint id PK
        bigint cuti_id FK "(ke cutis)"
        bigint kriteria_id FK "(ke kriterias)"
        decimal nilai "10.4, snapshot nilai kriteria"
        timestamp created_at
        timestamp updated_at
    }

    NOTIFICATIONS {
        uuid id PK
        varchar type
        varchar notifiable_type "polimorfik"
        bigint notifiable_id "polimorfik"
        text data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o| EMPLOYEES : "1 akun : 0..1 profil pegawai"
    UNIT_KERJAS ||--o{ USERS : "1 unit : 0..N anggota (users.unit_kerja_id)"
    USERS ||--o| UNIT_KERJAS : "1 user memimpin 0..1 unit (ketua_user_id)"
    UNIT_KERJAS ||--o{ EMPLOYEES : "1 unit : 0..N pegawai"
    EMPLOYEES ||--o{ CUTIS : "1 pegawai : 0..N pengajuan"
    USERS ||--o{ CUTIS : "approver tahap 1/2/3"
    UNIT_KERJAS ||--o{ BEBAN_KERJA_UNITS : "1 unit : 0..N periode"
    CUTIS ||--o{ PENILAIAN_CUTIS : "1 pengajuan : 5 snapshot kriteria"
    KRITERIAS ||--o{ PENILAIAN_CUTIS : "1 kriteria : 0..N nilai"
    USERS ||..o{ NOTIFICATIONS : "notifiable polimorfik"
```

## Ringkasan Relasi App-Level (Eloquent)

| Model | Relasi | Keterangan |
|-------|--------|------------|
| `User` | `employee` (HasOne) | Profil pegawai penautan akun; `null` artinya akun non-pegawai |
| `Employee` | `user` (BelongsTo), `unitKerja` (BelongsTo), `cutis` (HasMany) | Sumber C1, C2, C3 |
| `UnitKerja` | `ketua` (BelongsTo → User), `employees` (HasMany), `bebanKerjaUnits` (HasMany) | Ketua = approver tahap 1 |
| `Cuti` | `employee`, `atasanLangsungApprover`, `tataUsahaApprover`, `kepalaApprover` (BelongsTo → User), `penilaianCutis` (HasMany) | Approver disimpan per tahap |
| `PenilaianCuti` | `cuti` (BelongsTo), `kriteria` (BelongsTo) | Unique `(cuti_id, kriteria_id)` — matriks keputusan SAW |
| `BebanKerjaUnit` | `unitKerja` (BelongsTo) | Unique `(unit_kerja_id, periode)` — sumber C5 |
| `Notification` | polimorfik (`notifiable`) | Bells/UI notifikasi; penerima bisa User maupun Employee |

## Catatan Desain

- **Referensi silang Users ⇄ UnitKerjas**: `users.unit_kerja_id → unit_kerjas` dan `unit_kerjas.ketua_user_id → users`. Keduanya nullable + `nullOnDelete`, dan penunjukan ketua hanya dikelola lewat observer pada `App\Models\User` (penggantian role/ketua mereset `ketua_user_id` lama).
- **`status_global`**: string gerbang alur (`Menunggu Persetujuan Atasan Langsung`, `... Tata Usaha`, `... Kepala`, `Disetujui`, `Ditolak (oleh <penyetuju>)`). Penyebut penolakan ikut tersimpan sehingga riwayat alur tercatat; filter memakai pencocokan awalan `Ditolak`. Konsumsi status cukup lewat konstanta `App\Models\Cuti::STATUS_*`.
- **`cutis.detail_tanggal`** adalah kolom *legacy* dari versi awal pemilih tanggal — semua kode kini memakai `tanggal_cuti_array`; kolom ini boleh dibuang di migrasi pembersihan berikutnya.
- **Kolom SAW** (`skor_prioritas`, `ranking`) hanya terisi bila pengajuan masih pending pada periode aktif; langsung dinull-kan setelah keputusan final.
- **Saldo cuti** (`employees.sisa_cuti_tahunan`) dipotong saat approval tahap 3 (final) dan dikembalikan bila pengajuan disetujui dihapus — selalu dalam transaksi + `lockForUpdate`.

## Menjalankan Migrasi dari 0

```bash
php artisan migrate --seed     # + seeder akun demo & kriteria SAW
php artisan db:seed --class=SawDemoSeeder   # data demo perangkingan (opsional)
```
