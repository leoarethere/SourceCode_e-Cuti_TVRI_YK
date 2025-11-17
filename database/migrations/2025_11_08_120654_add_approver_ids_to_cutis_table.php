<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->foreignId('sdm_approver_id')->nullable()->after('tanggapan_sdm')->constrained('users')->nullOnDelete();
            $table->foreignId('tata_usaha_approver_id')->nullable()->after('tanggapan_tata_usaha')->constrained('users')->nullOnDelete();
            $table->foreignId('kepala_stasiun_approver_id')->nullable()->after('tanggapan_kepala')->constrained('users')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sdm_approver_id');
            $table->dropConstrainedForeignId('tata_usaha_approver_id');
            $table->dropConstrainedForeignId('kepala_stasiun_approver_id');
        });
    }
};
