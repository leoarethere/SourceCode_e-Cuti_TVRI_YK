<?php

namespace Tests\Unit;

use App\Services\SawService;
use InvalidArgumentException;
use Tests\TestCase;

class SawServiceTest extends TestCase
{
    private const DELTA = 0.000001;

    /**
     * Matriks keputusan final (Matrix B) sesuai BAB IV yang sudah dikoreksi.
     *
     * @return array<int, array{id:string, values:array<string,float>}>
     */
    private function alternatives(): array
    {
        return [
            ['id' => 'A1', 'values' => ['C1' => 10, 'C2' => 85, 'C3' => 3, 'C4' => 4, 'C5' => 75]],
            ['id' => 'A2', 'values' => ['C1' => 8,  'C2' => 90, 'C3' => 5, 'C4' => 5, 'C5' => 80]],
            ['id' => 'A3', 'values' => ['C1' => 12, 'C2' => 70, 'C3' => 1, 'C4' => 2, 'C5' => 50]],
            ['id' => 'A4', 'values' => ['C1' => 6,  'C2' => 95, 'C3' => 4, 'C4' => 3, 'C5' => 60]],
            ['id' => 'A5', 'values' => ['C1' => 9,  'C2' => 75, 'C3' => 2, 'C4' => 1, 'C5' => 75]],
        ];
    }

    /**
     * @return array<int, array{kode:string, tipe:string, bobot:float}>
     */
    private function criteria(): array
    {
        return [
            ['kode' => 'C1', 'tipe' => 'benefit', 'bobot' => 0.30],
            ['kode' => 'C2', 'tipe' => 'benefit', 'bobot' => 0.25],
            ['kode' => 'C3', 'tipe' => 'benefit', 'bobot' => 0.15],
            ['kode' => 'C4', 'tipe' => 'benefit', 'bobot' => 0.20],
            ['kode' => 'C5', 'tipe' => 'cost',    'bobot' => 0.10],
        ];
    }

    private function resultsByAlternative(): array
    {
        $results = app(SawService::class)->calculate($this->alternatives(), $this->criteria());

        return collect($results)->keyBy('id')->all();
    }

    public function test_normalisasi_benefit_sesuai_bab_iv(): void
    {
        $results = $this->resultsByAlternative();

        $expected = [
            'A1' => ['C1' => 0.833333, 'C2' => 0.894737, 'C3' => 0.600000, 'C4' => 0.800000],
            'A2' => ['C1' => 0.666667, 'C2' => 0.947368, 'C3' => 1.000000, 'C4' => 1.000000],
            'A3' => ['C1' => 1.000000, 'C2' => 0.736842, 'C3' => 0.200000, 'C4' => 0.400000],
            'A4' => ['C1' => 0.500000, 'C2' => 1.000000, 'C3' => 0.800000, 'C4' => 0.600000],
            'A5' => ['C1' => 0.750000, 'C2' => 0.789474, 'C3' => 0.400000, 'C4' => 0.200000],
        ];

        foreach ($expected as $alternative => $values) {
            foreach ($values as $kode => $value) {
                $this->assertEqualsWithDelta(
                    $value,
                    $results[$alternative]['normalized'][$kode],
                    self::DELTA,
                    "Normalisasi {$alternative}-{$kode} tidak sesuai."
                );
            }
        }
    }

    public function test_normalisasi_cost_sesuai_bab_iv(): void
    {
        $results = $this->resultsByAlternative();

        $expected = [
            'A1' => 0.666667,
            'A2' => 0.625000,
            'A3' => 1.000000,
            'A4' => 0.833333,
            'A5' => 0.666667,
        ];

        foreach ($expected as $alternative => $value) {
            $this->assertEqualsWithDelta(
                $value,
                $results[$alternative]['normalized']['C5'],
                self::DELTA,
                "Normalisasi cost {$alternative}-C5 tidak sesuai."
            );
        }
    }

    public function test_skor_preferensi_sesuai_bab_iv(): void
    {
        $results = $this->resultsByAlternative();

        $expected = [
            'A1' => 0.790351,
            'A2' => 0.849342,
            'A3' => 0.694211,
            'A4' => 0.723333,
            'A5' => 0.589035,
        ];

        foreach ($expected as $alternative => $value) {
            $this->assertEqualsWithDelta(
                $value,
                $results[$alternative]['skor'],
                self::DELTA,
                "Skor {$alternative} tidak sesuai BAB IV."
            );
        }
    }

    public function test_ranking_sesuai_bab_iv(): void
    {
        $results = collect($this->resultsByAlternative())
            ->sortBy('ranking')
            ->pluck('id')
            ->values()
            ->all();

        $this->assertSame(['A2', 'A1', 'A4', 'A3', 'A5'], $results);
    }

    public function test_bobot_tidak_sama_dengan_satu_melempar_exception(): void
    {
        $criteria = $this->criteria();
        $criteria[0]['bobot'] = 0.20; // total menjadi 0.90

        $this->expectException(InvalidArgumentException::class);

        app(SawService::class)->calculate($this->alternatives(), $criteria);
    }

    public function test_nilai_benefit_nol_tidak_error(): void
    {
        $alternatives = $this->alternatives();

        foreach ($alternatives as &$alternative) {
            $alternative['values']['C2'] = 0;
        }

        $results = app(SawService::class)->calculate($alternatives, $this->criteria());

        foreach ($results as $result) {
            $this->assertSame(0.0, $result['normalized']['C2']);
        }
    }

    public function test_nilai_cost_nol_tidak_error(): void
    {
        $alternatives = $this->alternatives();

        $alternatives[0]['values']['C5'] = 0;
        $alternatives[1]['values']['C5'] = 0;

        $results = app(SawService::class)->calculate($alternatives, $this->criteria());

        $this->assertIsArray($results);
        $this->assertCount(5, $results);
    }

    public function test_satu_alternatif_mendapat_skor_satu(): void
    {
        $results = app(SawService::class)->calculate(
            [['id' => 'A1', 'values' => ['C1' => 10, 'C2' => 85, 'C3' => 3, 'C4' => 4, 'C5' => 75]]],
            $this->criteria()
        );

        $this->assertEqualsWithDelta(1.0, $results[0]['skor'], self::DELTA);
        $this->assertSame(1, $results[0]['ranking']);
    }

    public function test_matrix_kosong_mengembalikan_array_kosong(): void
    {
        $this->assertSame([], app(SawService::class)->calculate([], $this->criteria()));
    }

    public function test_tie_breaker_mempertahankan_urutan_input(): void
    {
        $alternatives = [
            ['id' => 'A1', 'values' => ['C1' => 10, 'C2' => 80, 'C3' => 3, 'C4' => 4, 'C5' => 50]],
            ['id' => 'A2', 'values' => ['C1' => 10, 'C2' => 80, 'C3' => 3, 'C4' => 4, 'C5' => 50]],
        ];

        $results = app(SawService::class)->calculate($alternatives, $this->criteria());

        $this->assertSame('A1', $results[0]['id']);
        $this->assertSame('A2', $results[1]['id']);
    }

    public function test_matriks_asli_a5_0581(): void
    {
        // Paritas dengan dokumen asli sebelum koreksi C5 Joko (85).
        $alternatives = $this->alternatives();
        $alternatives[4]['values']['C5'] = 85;

        $results = collect(
            app(SawService::class)->calculate($alternatives, $this->criteria())
        )->keyBy('id')->all();

        $this->assertEqualsWithDelta(0.581192, $results['A5']['skor'], self::DELTA);
    }
}
