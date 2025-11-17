<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            // Kolom text nullable (boleh kosong) untuk menyimpan link
            $table->text('lampiran_link')->nullable()->after('alamat_selama_cuti');
        });
    }

    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn('lampiran_link');
        });
    }
};
