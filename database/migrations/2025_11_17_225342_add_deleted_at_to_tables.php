<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes(); // Menambah kolom 'deleted_at'
        });
        
        Schema::table('employees', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('cutis', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
