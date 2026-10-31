<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuti_id')
                ->constrained('cutis')
                ->cascadeOnDelete();
            $table->foreignId('kriteria_id')
                ->constrained('kriterias')
                ->cascadeOnDelete();
            $table->decimal('nilai', 10, 4);
            $table->timestamps();

            $table->unique(['cuti_id', 'kriteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_cutis');
    }
};
