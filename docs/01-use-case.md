# 01 — Use Case Diagram (e-Cuti)

Diagram berikut ditulis dalam sintaks Mermaid `flowchart` (Mermaid belum punya bentuk use case bawaan — aktor diberi emoji 👤/⚙️, use case berbentuk kapsul/elips, dan batas sistem berupa `subgraph`).

Semua aktor & otorisasi diambil langsung dari kode sumber:

| Aktor (role DB) | Akses (dari kode: `canViewAny`/`canAccess`/`visible`) |
|---|---|
| Pegawai (`pegawai`) | Pengajuan sendiri, lihat status sendiri, cetak formulir, hapus pengajuan sendiri yang masih tahap 1 |
| Ketua Tim (`ketua_tim`) | Approve/tolak tahap 1 unitnya, perangkingan SAW unitnya, hapus pengajuan sendiri tahap 1 |
| Tata Usaha (`tata_usaha`) | Approve/tolak tahap 2, kelola beban kerja unit, kelola data pegawai, perangkingan SAW |
| Kepala Stasiun (`kepala_stasiun`) | Approve/tolak tahap 3 (potong saldo), perangkingan SAW |
| Admin (`admin`) | Seluruh master data (Unit Kerja, Kriteria, Pegawai, Pengguna), override status, hapus & export |

```mermaid
flowchart LR
    %% ══════════════ AKTOR ══════════════
    p(["👤 Pegawai<br/>(role: pegawai)"])
    k(["👤 Ketua Tim<br/>(role: ketua_tim)"])
    tu(["👤 Tata Usaha<br/>(role: tata_usaha)"])
    ks(["👤 Kepala Stasiun<br/>(role: kepala_stasiun)"])
    adm(["👤 Administrator<br/>(role: admin)"])
    sys(["⚙️ Sistem<br/>(otomatis: Observer & Notification)"])

    subgraph sistem ["🖥️ Sistem e-Cuti TVRI Yogyakarta"]
        UC_LOGIN(["Masuk / Keluar"])
        UC_SUBMIT(["Mengajukan cuti"])
        UC_VIEW(["Melihat status &amp; riwayat pengajuan"])
        UC_PRINT(["Mencetak formulir cuti (PDF)"])
        UC_DELETE(["Menghapus pengajuan sendiri<br/>(masih tahap 1)"])

        UC_A1(["Menyetujui / menolak pengajuan<br/>Tahap 1 — Ketua Unit"])
        UC_A2(["Memverifikasi pengajuan<br/>Tahap 2 — Tata Usaha"])
        UC_A3(["Menyetujui / menolak final<br/>Tahap 3 — Kepala Stasiun"])

        UC_RANK(["Melihat / menghitung<br/>perangkingan SAW"])
        UC_EKSPOR(["Mengekspor rekap<br/>Excel / PDF"])

        UC_UNIT(["Mengelola Unit Kerja<br/>+ penunjukan ketua"])
        UC_KRITERIA(["Mengelola Kriteria<br/>&amp; bobot SAW (C1–C5)"])
        UC_PEGAWAI(["Mengelola data Pegawai"])
        UC_PENGGUNA(["Mengelola Pengguna &amp; role"])
        UC_BEBAN(["Mengelola Beban Kerja Unit<br/>per periode (C5)"])
        UC_OVERRIDE(["Override status pengajuan"])

        UC_SALDO(["Memvalidasi saldo<br/>cuti tahunan"])
        UC_TANGGAL(["Memvalidasi tanggal<br/>(backdate &amp; bentrok)"])
        UC_BYPASS(["Bypass tahap 1<br/>(pengaju = ketua unit)"])
        UC_POTONG(["Memotong saldo:<br/>sisa cuti tahunan"])
        UC_NOTIF(["Menerima notifikasi<br/>(database &amp; email)"])
        UC_SAW(["Menghitung ulang SAW<br/>(observer otomatis)"])
    end

    %% ══════════════ PEMEGANG AKTOR ══════════════
    p --- UC_LOGIN
    k --- UC_LOGIN
    tu --- UC_LOGIN
    ks --- UC_LOGIN
    adm --- UC_LOGIN

    p --> UC_SUBMIT
    adm --> UC_SUBMIT
    p --> UC_VIEW
    k --> UC_VIEW
    tu --> UC_VIEW
    ks --> UC_VIEW
    p --> UC_PRINT
    p --> UC_DELETE
    adm --> UC_DELETE

    k --> UC_A1
    tu --> UC_A2
    ks --> UC_A3
    adm --> UC_OVERRIDE
    adm --> UC_UNIT
    adm --> UC_KRITERIA
    adm --> UC_PENGGUNA
    tu --> UC_PEGAWAI
    adm --> UC_PEGAWAI
    tu --> UC_BEBAN
    adm --> UC_BEBAN

    k --> UC_RANK
    tu --> UC_RANK
    ks --> UC_RANK
    adm --> UC_RANK
    tu --> UC_EKSPOR
    adm --> UC_EKSPOR
    ks --> UC_EKSPOR

    %% ══════════════ «include» ══════════════
    UC_SUBMIT -.->|"«include»"| UC_SALDO
    UC_SUBMIT -.->|"«include»"| UC_TANGGAL
    UC_A3 -.->|"«include»"| UC_POTONG

    %% ══════════════ «extend» ══════════════
    UC_BYPASS -.->|"«extend» bila pengaju<br/>adalah ketua unit"| UC_SUBMIT

    %% ══════════════ Interaksi sistem ══════════════
    sys --> UC_SAW
    sys --> UC_NOTIF
    UC_A1 -.- UC_SAW
    UC_A2 -.- UC_SAW
    UC_A3 -.- UC_NOTIF

    %% ══════════════ GAYA ══════════════
    style p fill:#e0f2fe,stroke:#0369a1
    style k fill:#dcfce7,stroke:#15803d
    style tu fill:#fef9c3,stroke:#a16207
    style ks fill:#f3e8ff,stroke:#7e22ce
    style adm fill:#fee2e2,stroke:#b91c1c
    style sys fill:#f1f5f9,stroke:#334155
    style sistem fill:#fafafa,stroke:#94a3b8
```

## Catatan Otorisasi (dari kodesumber)

- **Pengajuan**: dibuat oleh pemilik data (`mutateFormDataBeforeCreate` mengunci `employee_id` — non-admin selalu dirinya sendiri); Admin dapat membuatkan pengajuan atas nama pegawai mana pun.
- **Bypass tahap 1**: bila pengaju adalah ketua unit itu sendiri (`CreateCuti::mutateFormDataBeforeCreate`), pengajuan langsung memasuki tahap Tata Usaha — mencegah *self-approval*.
- **Approve tahap 1** hanya mengizinkan ketua unit yang menyetujui pengajuan **pegawai lain** (bukan pengajunya sendiri — `CutiResource::canProsesSebagaiKetua()`).
- **Tahap 3 (`UC_A3`)**: satu-satunya titik pemotongan saldo cuti tahunan, dengan transaksi + `lockForUpdate` (mencegah *overspend* saat dua proses approval berbarengan), sekaligus memicu notifikasi sukses/gagal ke pegawai.
- **Ekspor**: dari tombol pada halaman Data Pengajuan Cuti; isinya mengikuti filter & otorisasi data yang terlihat bagi peran masing-masing (pegawai hanya mencetak data sendiri).
