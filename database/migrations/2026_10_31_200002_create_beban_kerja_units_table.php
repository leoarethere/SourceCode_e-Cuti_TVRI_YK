<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beban_kerja_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_kerja_id')
                ->constrained('unit_kerjas')
                ->cascadeOnDelete();
            $table->char('periode', 7);                   // 2026-10
            $table->decimal('nilai', 5, 2);               // 0-100
            $table->timestamps();

            $table->unique(['unit_kerja_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beban_kerja_units');
    }
};
