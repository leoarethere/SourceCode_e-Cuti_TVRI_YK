<?php

namespace App\Console\Commands;

use App\Models\Cuti;
use App\Services\SawService;
use Illuminate\Console\Command;

class RecomputeSaw extends Command
{
    protected $signature = 'saw:recompute {periode? : Periode dengan format YYYY-MM}';

    protected $description = 'Hitung ulang skor dan ranking SAW untuk satu atau semua periode';

    public function handle(SawService $saw): int
    {
        $periode = $this->argument('periode');

        $periodes = $periode
            ? [$periode]
            : Cuti::query()
                ->whereNotNull('periode')
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode')
                ->all();

        if (empty($periodes)) {
            $this->warn('Tidak ada periode cuti yang bisa diproses.');

            return self::SUCCESS;
        }

        foreach ($periodes as $item) {
            try {
                $hasil = $saw->hitungPrioritas($item);
                $this->info("Periode {$item}: {$hasil->count()} pengajuan diproses.");
            } catch (\Throwable $e) {
                $this->error("Periode {$item} gagal: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
