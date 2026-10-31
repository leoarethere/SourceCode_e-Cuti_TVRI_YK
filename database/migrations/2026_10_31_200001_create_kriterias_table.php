<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kriterias', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 5)->unique();          // C1..C5
            $table->string('nama_kriteria');
            $table->enum('tipe', ['benefit', 'cost']);
            $table->decimal('bobot', 5, 4);               // 0.3000
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kriterias');
    }
};
