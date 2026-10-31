<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->unsignedTinyInteger('urgensi')->nullable()->after('alasan');   // C4, 1-5
            $table->char('periode', 7)->nullable()->index()->after('tanggal_akhir');
            $table->decimal('skor_prioritas', 8, 6)->nullable()->after('status_global');
            $table->unsignedInteger('ranking')->nullable()->after('skor_prioritas');
        });
    }

    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn(['urgensi', 'periode', 'skor_prioritas', 'ranking']);
        });
    }
};
