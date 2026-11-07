# e-Cuti — Sistem Informasi Pengajuan & Perangkingan Cuti TVRI Stasiun Yogyakarta

Aplikasi web untuk pengelolaan pengajuan cuti pegawai di Lingkungan TVRI Stasiun D.I. Yogyakarta, dilengkapi alur persetujuan bertingkat (`Ketua Unit → Tata Usaha → Kepala Stasiun`) dan Sistem Pendukung Keputusan (SPK) berbasis metode **SAW (Simple Additive Weighting)** untuk menentukan prioritas cuti.

Dibangun dengan **Laravel 12 + Filament 3** sebagai panel administrasi, **TCPDF** untuk formulir/rekap PDF, dan **Maatwebsite Excel** untuk rekap XLSX.

---

## ✨ Fitur Utama

- **Autentikasi & multi-role**: `admin`, `kepala_stasiun`, `tata_usaha`, `ketua_tim`, `pegawai`
- **Pengajuan cuti dengan pemilih tanggal visual** (bisa acak / tidak berurutan)
- **Alur approval 3 tahap** dengan notifikasi database (bell Filament):
  1. Ketua Unit — memvalidasi alasan & kesesuaian tugas
  2. Tata Usaha — verifikasi administrasi (saldo, dokumen)
  3. Kepala Stasiun — persetujuan final; saldo cuti tahunan otomatis dipotong
- **Anti self-approval**: pengajuan cuti oleh ketua unit otomatis diberlakukan *bypass* tahap 1 dan langsung diproses Tata Usaha
- **Perangkingan SAW** periode `YYYY-MM` dengan halaman SPK tersendiri (rincian normalisasi & kontribusi tiap kriteria dapat dilihat per pengajuan)
- **Ekspor**: Rekap Excel (.xlsx) dan PDF rekap + formulir cuti per pegawai (TCPDF)
- **Notifikasi email** status pengajuan (`CutiStatusUpdated`, dikirim melalui queue) + database notification
- **Audit saldo aman**: pemotongan/pengembalian saldo cuti tahunan dilakukan dalam transaksi DB dengan `lockForUpdate` agar tidak *overspend* saat approval berbarengan

---

## 🧮 Metode SAW

Setiap pengajuan cuti pending pada sebuah periode membentuk matriks keputusan dengan 5 kriteria:

| Kode | Kriteria | Tipe | Sumber Nilai |
|------|----------|------|--------------|
| C1 | Sisa cuti tahunan | Benefit | `employees.sisa_cuti_tahunan` (saat pengajuan; saldo baru dipotong setelah final) |
| C2 | Nilai kinerja | Benefit | `employees.nilai_kinerja` |
| C3 | Masa kerja | Benefit | `tanggal_bergabung` dihitung terhadap **tanggal pengajuan cuti** (bukan waktu perhitungan, agar ranking stabil) |
| C4 | Tingkat urgensi | Benefit | input pengaju (1–5) |
| C5 | Beban kerja unit | Cost | `beban_kerja_units.nilai` per unit per periode |

Normalisasi mengikuti inversi kelas utilitas:

- **Benefit**: `r_ij = v_ij / max_j`
- **Cost**: `r_ij = min_j / v_ij` (jika `v_ij > 0`)

Skor akhir: `S_i = Σ (w_j · r_ij)`, ranking = urutan menurun skor (tie-breaker: urutan *created_at* → *id*).

Total bobot kriteria wajib `1.0000` (disvalidasi sebelum setiap perhitungan; bobot dapat diatur di menu **SPK → Kriteria**). Nilai tiap kriteria disimpan sebagai *snapshot* pada tabel `penilaian_cutis` sehingga detail perhitungan historis tetap dapat dilihat.

Ranking hanya berlaku untuk pengajuan yang masih **pending**; setiap perubahan status/urgensi/tanggal memicu hitung ulang otomatis (observer), dan dapat dijalankan manual:

```bash
php artisan saw:recompute          # semua periode
php artisan saw:recompute 2026-11  # periode tertentu (YYYY-MM)
```

Perhitungan **Perangkingan SAW** juga bisa dipicu langsung dari halaman **SPK → Perangkingan SAW**.

---

## 🚀 Instalasi

Prasyarat: PHP ≥ 8.2, Composer, Node.js ≥ 20, MySQL/MariaDB.

```bash
# 1. Clone & dependensi
git clone https://github.com/leoarethere/e-Cuti.git
cd e-Cuti
composer install
npm install && npm run build

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate
# Atur DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env
# (Opsional) MAIL_* untuk fungsional email + QUEUE_CONNECTION=database

# 3. Migrasi & data awal
php artisan migrate --seed

# 4. Jalankan
php artisan serve
# atau jalani dev lengkap (server + queue worker + pail + vite):
composer dev
```

Jika menggunakan email antrian (`QUEUE_CONNECTION=database`), jalankan `php artisan queue:listen` (sudah dibungkus oleh `composer dev`).

---

## 👤 Akun Demo (hasil seeder)

Password untuk **semua** akun demo di bawah ini adalah:

```
password
```

| Nama | Email | Peran |
|------|-------|-------|
| Administrator | `admin@tvri.go.id` | Admin (full akses) |
| Kepala Stasiun TVRI | `kepala@tvri.go.id` | Kepala Stasiun (approval tahap 3) |
| Ibu Tata Usaha | `tu@tvri.go.id` | Tata Usaha (approval tahap 2) |
| Ketua Tim Berita | `ketuaberita@tvri.go.id` | Ketua Tim (approval tahap 1 untuk Tim Berita) |
| Budi Jurnalis | `budi@tvri.go.id` | Pegawai Tim Berita (pengaju cuti) |
| Siti Kameramen | `siti@tvri.go.id` | Pegawai Tim Teknik (pengaju cuti) |

Seeder juga mengisi bobot kriteria SAW default (lihat `database/seeders/KriteriaSeeder.php`). Untuk demo data perangkingan lebih kaya, gunakan `php artisan db:seed --class=SawDemoSeeder`.

⚠️ **Sebentar lagi ganti password semua akun demo pada environment produksi.**

---

## 🔐 Alur Persetujuan Singkat

```
Pegawai mengajukan
        │
        ▼  status: Menunggu Persetujuan Atasan Langsung
   Ketua Unit ── tolak ──▶ Ditolak (oleh Ketua <unit>)
        │ setuju
        ▼  status: Menunggu Persetujuan Tata Usaha
   Tata Usaha ── tolak ──▶ Ditolak (oleh Tata Usaha)
        │ setuju
        ▼  status: Menunggu Persetujuan Kepala
   Kepala Stasiun ── tolak ──▶ Ditolak (oleh Kepala Stasiun)
        │ setuju
        ▼  status: Disetujui  (saldo cuti tahunan dipotong)
```

Setiap penolakan menyimpan penyebutnya pada `status_global` sehingga riwayat alur dapat dilacak; filter status di tabel menggunakan pencocokan awalan `Ditolak`.

---

## 🧪 Testing

```bash
php artisan test
```

Mencakup unit test normalisasi/ranking SAW, integrasi seeder–observer–ekspor, serta uji otorisasi halaman Filament per peran.

---

## 📄 Lisensi

Copyright © TVRI Stasiun D.I. Yogyakarta. Seluruh hak dilindungi.

Didesain & dikembangkan oleh **Leonardo Putra Susanto**, **Desty Wahyu Anjani**, dan **Ilham Bagaskara**.
