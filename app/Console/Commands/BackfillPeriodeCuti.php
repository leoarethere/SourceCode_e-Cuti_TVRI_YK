<?php

namespace App\Console\Commands;

use App\Models\Cuti;
use App\Services\SawService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillPeriodeCuti extends Command
{
    protected $signature = 'saw:backfill-periode';

    protected $description = 'Isi kolom periode pada data cuti lama berdasarkan tanggal mulai';

    public function handle(SawService $saw): int
    {
        $jumlah = 0;

        Cuti::query()
            ->where(function ($query) {
                $query->whereNull('periode')->orWhere('periode', '');
            })
            ->chunkById(100, function ($cutis) use (&$jumlah) {
                foreach ($cutis as $cuti) {
                    if (! $cuti->tanggal_mulai) {
                        continue;
                    }

                    $cuti->periode = Carbon::parse($cuti->tanggal_mulai)->format('Y-m');
                    $cuti->saveQuietly();
                    $jumlah++;
                }
            });

        $this->info("{$jumlah} data cuti berhasil diisi periode-nya.");

        if ($jumlah > 0) {
            $periodes = Cuti::query()
                ->whereNotNull('periode')
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode')
                ->all();

            foreach ($periodes as $periode) {
                try {
                    $saw->hitungPrioritas($periode);
                    $this->line("Ranking dihitung ulang untuk periode {$periode}.");
                } catch (\Throwable $e) {
                    $this->warn("Ranking periode {$periode} gagal: {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }
}
