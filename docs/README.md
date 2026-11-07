# Indeks Dokumentasi Diagram — e-Cuti

Keempat diagram model sistem, semuanya dalam **kode Mermaid** (GitHub merendernya otomatis saat file dibuka). Semuanya disusun dari kode sumber & skema database aktif proyek ini.

| # | Diagram | File | Sumber Kebenaran |
|---|---------|------|------------------|
| 1 | **Use Case Diagram** | [`01-use-case.md`](./01-use-case.md) | Role & otorisasi per halaman (UnitKerja/Kriteria/Pegawai/Pengguna/BebanKerja/Cuti resource, `PerangkinganCuti`) |
| 2 | **Activity Diagram** | [`02-activity.md`](./02-activity.md) | `CreateCuti`, `EditCuti`, `CutiResource` (aksi approval 3 tahap), `CutiObserver` |
| 3 | **Class Diagram** | [`03-class-diagram.md`](./03-class-diagram.md) | `app/Models/*`, `SawService`, `CutiObserver`, `CutiStatusUpdated` |
| 4 | **ERD (Entity Relationship Diagram)** | [`DATABASE.md`](./DATABASE.md) | Skema MySQL aktif (diekspor live, termasuk seluruh riwayat migrasi) |

## Rangkuman Alur Utama

```
Pegawai mengajukan cuti
      │  validasi saldo (Cuti Tahunan) + tanggal (backdate/bentrok)
      ▼
[Tahap 1] Ketua Unit ──(bypass bila pengaju = ketua)──┐
      ▼                                              │
[Tahap 2] Tata Usaha ◄───────────────────────────────┘
      ▼
[Tahap 3] Kepala Stasiun → potong saldo (lockForUpdate) → Disetujui
      │
      ▼
CutiObserver → SawService::hitungPrioritas(periode) → skor & ranking SAW
```

Lampiran pendukung:
- `database/seeders/KriteriaSeeder.php` — bobot default kriteria C1–C5
- `php artisan saw:recompute [periode]` — hitung ulang manual
