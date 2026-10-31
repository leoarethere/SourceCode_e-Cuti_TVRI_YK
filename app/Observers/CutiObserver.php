<?php

namespace App\Observers;

use App\Models\Cuti;
use App\Services\SawService;
use Illuminate\Support\Facades\Log;

class CutiObserver
{
    public function saved(Cuti $cuti): void
    {
        if (! $cuti->periode) {
            return;
        }

        $relevan = $cuti->wasRecentlyCreated
            || $cuti->wasChanged(['status_global', 'urgensi', 'tanggal_mulai', 'periode']);

        if (! $relevan) {
            return;
        }

        $this->recompute($cuti->periode);

        $periodeLama = $cuti->getOriginal('periode');

        if ($periodeLama && $periodeLama !== $cuti->periode) {
            $this->recompute($periodeLama);
        }
    }

    public function deleted(Cuti $cuti): void
    {
        if ($cuti->periode) {
            $this->recompute($cuti->periode);
        }
    }

    public function restored(Cuti $cuti): void
    {
        if ($cuti->periode) {
            $this->recompute($cuti->periode);
        }
    }

    private function recompute(string $periode): void
    {
        try {
            app(SawService::class)->hitungPrioritas($periode);
        } catch (\Throwable $e) {
            // Jangan sampai kegagalan SPK memblokir penyimpanan cuti.
            Log::warning('Perhitungan SAW gagal: ' . $e->getMessage(), [
                'periode' => $periode,
            ]);
        }
    }
}
