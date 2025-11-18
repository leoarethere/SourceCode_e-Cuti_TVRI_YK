<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel unit_kerjas dulu
        Schema::create('unit_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('ketua_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Backup data unit_kerja lama dan buat record di unit_kerjas
        $employees = DB::table('employees')->whereNotNull('unit_kerja')->get();
        
        $unitKerjaMap = [];
        foreach ($employees as $employee) {
            if (!isset($unitKerjaMap[$employee->unit_kerja])) {
                $id = DB::table('unit_kerjas')->insertGetId([
                    'nama' => $employee->unit_kerja,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $unitKerjaMap[$employee->unit_kerja] = $id;
            }
        }

        // 3. Update tabel employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('unit_kerja_id')->nullable()
                ->after('jabatan')->constrained('unit_kerjas')->nullOnDelete();
        });

        // 4. Migrate data ke kolom baru
        foreach ($employees as $employee) {
            if (isset($unitKerjaMap[$employee->unit_kerja])) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['unit_kerja_id' => $unitKerjaMap[$employee->unit_kerja]]);
            }
        }

        // 5. Baru drop kolom lama
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('unit_kerja');
        });

        // 6. Update tabel cutis
        Schema::table('cutis', function (Blueprint $table) {
            $table->renameColumn('status_sdm', 'status_atasan_langsung');
            $table->renameColumn('tanggapan_sdm', 'tanggapan_atasan_langsung');
            $table->renameColumn('sdm_approver_id', 'atasan_langsung_approver_id');
        });
    }

    public function down(): void
    {
        // (Logic rollback disederhanakan)
        Schema::dropIfExists('unit_kerjas');
    }
};