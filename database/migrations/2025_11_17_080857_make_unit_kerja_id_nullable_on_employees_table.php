<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Ubah kolom agar BOLEH KOSONG (nullable)
            $table->foreignId('unit_kerja_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Kembalikan ke wajib isi (jika perlu rollback)
            $table->foreignId('unit_kerja_id')->nullable(false)->change();
        });
    }
};