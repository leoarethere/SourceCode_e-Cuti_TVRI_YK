<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            // Tambah kolom JSON untuk menyimpan array tanggal yang dipilih
            $table->json('tanggal_cuti_array')->nullable()->after('tanggal_akhir');
            
            // PENTING: tanggal_mulai dan tanggal_akhir tetap kita pakai 
            // untuk keperluan range display dan filter cepat
        });
    }

    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn('tanggal_cuti_array');
        });
    }
};