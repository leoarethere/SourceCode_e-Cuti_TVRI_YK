# 02 — Activity Diagram (e-Cuti)

Diagram aktivitas alur pengajuan → persetujuan tiga tahap → finalisasi, ditulis dalam Mermaid `flowchart TD`. Setiap `subgraph` berperan sebagai *swimlane* aktor; urutan keputusan & cabang diambil langsung dari `CreateCuti`, `CutiResource`, dan `SawService`.

```mermaid
flowchart TD
    Start([Mulai]) --> LOGIN[/<b>👤 Pengaju</b><br/>Login / buka form pengajuan/]
    A2["<b>👤 Pengaju</b><br/>Isi formulir:<br/>jenis cuti, tanggal (bisa acak),<br/>urgensi C4, alasan, alamat, lampiran"]
    LOGIN --> A2

    %% ═════════ VALIDASI SISTEM ═════════
    subgraph SYS ["⚙️ Validasi otomatis (Sistem)"]
        V1{"Cuti Tahunan?"}
        V2{"Saldo cukup<br/>untuk N hari?"}
        V3{"Ada tanggal<br/>backdate?"}
        V4{"Tanggal bentrok<br/>dengan pengajuan lain?"}
        V5{"Pengaju =<br/>ketua unit?"}
    end

    A2 --> V1
    V1 -- Ya --> V2
    V1 -- Tidak --> V3
    V2 -- "Saldo kurang"--> X1[Kirim notifikasi:<br/>"Sisa cuti tidak cukup"] --> EF(["Selesai<br/>(tidak tersimpan)"])
    V2 -- Cukup --> V3
    V3 -- "Ya (pegawai/ketua)" --> X1
    V3 -- Tidak --> V4
    V4 -- "Bentrok" --> X1
    V4 -- Aman --> V5    V5 -- Ya --> S1["<b>Sistem</b><br/>status_atasan_langsung = approved<br/>status_global = <b>Menunggu TU</b><br/>(bypass — anti self-approval)"]
    V5 -- Tidak --> S2["<b>Sistem</b><br/>status_global =<br/><b>Menunggu Atasan Langsung</b>"]

    S1 --> N1["⚙️ Kirim notifikasi <br/>ke Tata Usaha"]
    S2 --> N2["⚙️ Kirim notifikasi<br/>ke Ketua Unit"]
    S1 --> OBS["⚙️ CutiObserver:<br/>hitung ulang prioritas SAW periode tsb"]
    S2 --> OBS

    %% ═════════ TAHAP 1 — KETUA ═════════
    subgraph LAP1 ["👤 Tahap 1 — Ketua Unit"]
        K1{"Setujui?"}
    end
    N2 --> K1
    OBS -. pengajuan kini tampil di daftar ketua .-> K1

    K1 -- Ditolak --> R1["status_global =<br/><b>Ditolak (oleh Ketua Unit)</b><br/>+ tanggapan"] --> EOBS1["⚙️ Hitung ulang SAW →<br/>beri tahu pegawai"] --> Final
    K1 -- Setujui --> A3["status_global =<br/><b>Menunggu Tata Usaha</b>"] --> N3["⚙️ Notifikasi ke<br/>Tata Usaha"] --> OBS --> T1

    %% ═════════ TAHAP 2 — TATA USAHA ═════════
    subgraph LAP2 ["👤 Tahap 2 — Tata Usaha"]
        T1{"Administrasi & dokumen<br/>sah & lengkap?"}
    end
    N3 --> T1

    T1 -- "Ditolak" --> R2["status_global =<br/><b>Ditolak (oleh Tata Usaha)</b>"] --> EOBS2["⚙️ Hitung ulang SAW +<br/>beri tahu pegawai"] --> Final
    T1 -- "Sah/OK" --> A4["status_global =<br/><b>Menunggu Kepala</b>"] --> N4["⚙️ Notifikasi ke<br/>Kepala Stasiun"] --> OBS --> KP

    %% ═════════ TAHAP 3 — KEPALA STASIUN ═════════
    subgraph LAP3 ["👤 Tahap 3 — Kepala Stasiun"]
        H1{"Keputusan final"}
    end
    N4 --> H1

    H1 -- "Tolak" --> R3["status_global =<br/><b>Ditolak (oleh Kepala Stasiun)</b>"] --> EOBS3["⚙️ Hitung ulang SAW +<br/>beri tahu pegawai"] --> Final
    H1 -- "Setujui | Cuti Tahunan" --> V6{"Saldo masih cukup?<br/>(lockForUpdate)"}
    H1 -- "Setujui | Cuti lainnya" --> A5
    V6 -- "Kurang" --> B1["Approval dibatalkan:<br/>status tetap <b>Menunggu Kepala</b><br/>+ peringatan 'saldo kurang' ke approver"] --> End
    V6 -- Cukup --> A5["SALDO: sisa_cuti_tahunan -= N<br/>status_global = <b>Disetujui</b>"] --> N5["⚙️ Notifikasi sukses<br/>(+ tautan cetak formulir)<br/>ke pegawai"] --> Final

    %% ═════════ TAMPILAN AKHIR PEGAWAI ═════════
    subgraph FIN ["👤 Pegawai"]
        Final{"Jelajahi status"}
        PDF[Cetak formulir cuti PDF<br/>bila telah <b>Disetujui</b>]
    end
    Final -- Disetujui --> PDF --> End([Selesai])
    Final -- Ditolak --> End

    %% ═════════ GAYA ═════════
    style SYS fill:#f1f5f9,stroke:#64748b
    style LAP1 fill:#dcfce7,stroke:#15803d
    style LAP2 fill:#fef9c3,stroke:#a16207
    style LAP3 fill:#f3e8ff,stroke:#7e22ce
    style FIN fill:#e0f2fe,stroke:#0369a1
    style X1 fill:#fee2e2,stroke:#b91c1c
    style R1 fill:#fee2e2,stroke:#b91c1c
    style R2 fill:#fee2e2,stroke:#b91c1c
    style R3 fill:#fee2e2,stroke:#b91c1c
    style B1 fill:#fef3c7,stroke:#b45309
    style A5 fill:#d1fae5,stroke:#047857
```

## Catatan penting

- **`OBS`** = hitung ulang SAW otomatis (`App\Observers\CutiObserver::saved` → `SawService::hitungPrioritas(periode)`). Terjadi pada setiap perubahan status/urgensi/tanggal/periode maupun saat pengajuan baru & penghapusan.
- **Backdate** hanya dibolehkan untuk Admin/TU/Kepala; pegawai & ketua tim ditolak jika memilih tanggal lampau.
- **Bypass tahap 1** adalah konsekuensi anti-self-approval: bila pengaju adalah ketua unit itu sendiri, pengajuan langsung masuk ke antrean Tata Usaha.
- **Tahap 3** adalah satu-satunya tempat pemotongan saldo: `sisa_cuti_tahunan` dipotong dalam transaksi database dengan `lockForUpdate`; bila saldo kurang, approval gagal aman dan status tetap `Menunggu Persetujuan Kepala`.
- Bila pengajuan yang telah disetujui kemudian dihapus, saldo yang telah dipotong **dikembalikan** otomatis (DeleteAction & BulkDelete).
