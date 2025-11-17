<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Tabel Unit Kerja
        Schema::create('unit_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Misal: "Tim SDM", "Tim Berita"
            // Ketua Unit adalah User (karena dia harus login untuk approve)
            $table->foreignId('ketua_user_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->timestamps();
        });

        // 2. Update Tabel Employees (Hapus kolom string lama, ganti ID)
        Schema::table('employees', function (Blueprint $table) {
            // Kita drop kolom string lama (WARNING: Data lama di kolom ini akan hilang)
            $table->dropColumn('unit_kerja'); 
            // Kita tambah relasi ke tabel unit_kerjas
            $table->foreignId('unit_kerja_id')->nullable()->after('jabatan')->constrained('unit_kerjas')->nullOnDelete();
        });

        // 3. Update Tabel Cutis (Generalisasi kolom SDM)
        Schema::table('cutis', function (Blueprint $table) {
            // Rename kolom SDM menjadi Atasan Langsung (agar bisa dipakai Ketua Berita, Teknik, dll)
            $table->renameColumn('status_sdm', 'status_atasan_langsung');
            $table->renameColumn('tanggapan_sdm', 'tanggapan_atasan_langsung');
            $table->renameColumn('sdm_approver_id', 'atasan_langsung_approver_id');
        });
    }

    public function down(): void
    {
        // (Logic rollback disederhanakan)
        Schema::dropIfExists('unit_kerjas');
    }
};