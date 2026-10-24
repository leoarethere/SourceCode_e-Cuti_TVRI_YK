<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            // Kita simpan daftar tanggal spesifik dalam format JSON
            $table->json('detail_tanggal')->nullable()->after('employee_id');
            
            // Kita juga butuh kolom 'lama_cuti' yang akurat (jumlah hari yang diceklis)
            // Karena sekarang (Tanggal Akhir - Tanggal Awal) belum tentu sama dengan jumlah cuti
            $table->integer('lama_cuti')->nullable()->after('detail_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn(['detail_tanggal', 'lama_cuti']);
        });
    }
};