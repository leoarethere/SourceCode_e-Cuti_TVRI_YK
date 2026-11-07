<?php

namespace App\Services;

use App\Models\BebanKerjaUnit;
use App\Models\Cuti;
use App\Models\Kriteria;
use App\Models\PenilaianCuti;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SawService
{
    /**
     * Hitung SAW murni dari matriks keputusan (tanpa database).
     *
     * @param  array<int, array{id:int|string, values:array<string,float>}>  $alternatives
     * @param  array<int, array{kode:string, tipe:string, bobot:float}>      $criteria
     * @return array<int, array{id:int|string, normalized:array<string,float>, skor:float, ranking:int}>
     */
    public function calculate(array $alternatives, array $criteria): array
    {
        if (empty($alternatives) || empty($criteria)) {
            return [];
        }

        $this->validateBobot(collect($criteria));

        $max = [];
        $min = [];

        foreach ($criteria as $criterion) {
            $values = array_map(
                fn (array $alternative) => (float) ($alternative['values'][$criterion['kode']] ?? 0),
                $alternatives
            );

            $max[$criterion['kode']] = max($values);
            $min[$criterion['kode']] = min($values);
        }

        $results = [];

        foreach ($alternatives as $order => $alternative) {
            $normalized = [];
            $total = 0.0;

            foreach ($criteria as $criterion) {
                $kode = $criterion['kode'];
                $nilai = (float) ($alternative['values'][$kode] ?? 0);

                if ($criterion['tipe'] === 'benefit') {
                    $ratio = $max[$kode] > 0
                        ? $nilai / $max[$kode]
                        : 0.0;
                } else {
                    if ($nilai > 0) {
                        $ratio = $min[$kode] / $nilai;
                    } else {
                        // Nilai 0 pada cost: jika minimum juga 0, ini nilai terbaik.
                        $ratio = $min[$kode] == 0 ? 1.0 : 0.0;
                    }
                }

                $normalized[$kode] = $ratio;
                $total += $ratio * (float) $criterion['bobot'];
            }

            $results[] = [
                'id'         => $alternative['id'],
                'normalized' => $normalized,
                'skor'       => $total,
                '_order'     => $order,
            ];
        }

        usort($results, function (array $a, array $b) {
            $comparison = $b['skor'] <=> $a['skor'];

            // Tie-breaker: urutan input (created_at, id) agar deterministik.
            return $comparison !== 0
                ? $comparison
                : $a['_order'] <=> $b['_order'];
        });

        foreach ($results as $index => &$result) {
            $result['ranking'] = $index + 1;
            unset($result['_order']);
        }

        return $results;
    }

    /**
     * Hitung, simpan snapshot penilaian, lalu update skor & ranking
     * untuk semua cuti pending pada periode tertentu.
     *
     * @return Collection<int, Cuti>
     */
    public function hitungPrioritas(string $periode): Collection
    {
        if (trim($periode) === '') {
            throw new InvalidArgumentException('Periode wajib diisi (format YYYY-MM).');
        }

        // Ranking hanya berlaku untuk pengajuan yang masih pending.
        // Bersihkan snapshot lama agar tidak muncul ranking ganda setelah diproses.
        Cuti::query()
            ->where('periode', $periode)
            ->whereNotIn('status_global', Cuti::STATUS_PENDING)
            ->update([
                'ranking' => null,
                'skor_prioritas' => null,
            ]);

        $cutis = Cuti::query()
            ->with(['employee.unitKerja', 'penilaianCutis.kriteria'])
            ->where('periode', $periode)
            ->whereIn('status_global', Cuti::STATUS_PENDING)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($cutis->isEmpty()) {
            return collect();
        }

        $kriteria = Kriteria::query()->orderBy('kode')->get();

        $this->validateBobot($kriteria);

        foreach ($cutis as $cuti) {
            $this->syncPenilaian($cuti, $kriteria);
            $cuti->load('penilaianCutis.kriteria');
        }

        $alternatives = $cutis->map(function (Cuti $cuti): array {
            return [
                'id' => $cuti->id,
                'values' => $cuti->penilaianCutis
                    ->mapWithKeys(fn (PenilaianCuti $penilaian) => [
                        $penilaian->kriteria->kode => (float) $penilaian->nilai,
                    ])
                    ->all(),
            ];
        })->all();

        $results = $this->calculate($alternatives, $kriteria->toArray());

        DB::transaction(function () use ($results): void {
            foreach ($results as $result) {
                Cuti::withoutEvents(function () use ($result): void {
                    Cuti::whereKey($result['id'])->update([
                        'skor_prioritas' => round($result['skor'], 6),
                        'ranking'        => $result['ranking'],
                    ]);
                });
            }
        });

        return $cutis->fresh(['employee.unitKerja', 'penilaianCutis.kriteria']);
    }

    /**
     * Susun matriks keputusan dari database untuk periode tertentu.
     *
     * @return array<int, array{id:int, values:array<string,float>}>
     */
    public function buildMatrix(string $periode): array
    {
        $kriteria = Kriteria::query()->orderBy('kode')->get();

        return Cuti::query()
            ->with(['penilaianCutis.kriteria'])
            ->where('periode', $periode)
            ->whereIn('status_global', Cuti::STATUS_PENDING)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (Cuti $cuti): array {
                return [
                    'id' => $cuti->id,
                    'values' => $cuti->penilaianCutis
                        ->mapWithKeys(fn (PenilaianCuti $penilaian) => [
                            $penilaian->kriteria->kode => (float) $penilaian->nilai,
                        ])
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * Sinkronkan nilai C1-C5 ke tabel penilaian_cutis.
     */
    public function syncPenilaian(Cuti $cuti, Collection $kriteria): void
    {
        $employee = $cuti->employee;

        // Masa kerja (C3) dihitung dari titik waktu pengajuan cuti, bukan
        // dari "sekarang". Tanpa ini, ranking bisa berubah begitu waktu
        // berjalan walau data pengajuan tidak berubah.
        $anchor = $cuti->created_at ?? now();

        $masaKerjaTahun = ($employee?->tanggal_bergabung)
            ? (int) Carbon::parse($employee->tanggal_bergabung)->diffInYears($anchor)
            : 0;

        $values = [
            'C1' => (float) ($employee?->sisa_cuti_tahunan ?? 0),
            'C2' => (float) ($employee?->nilai_kinerja ?? 0),
            'C3' => (float) $masaKerjaTahun,
            'C4' => (float) ($cuti->urgensi ?? 1),
            'C5' => $this->bebanKerja(
                (int) ($employee?->unit_kerja_id ?? 0),
                (string) $cuti->periode
            ),
        ];

        $rows = [];

        foreach ($kriteria as $kriteriaItem) {
            $rows[] = [
                'cuti_id'     => $cuti->id,
                'kriteria_id' => $kriteriaItem->id,
                'nilai'       => $values[$kriteriaItem->kode] ?? 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        // Satu bulk upsert menggantikan 5x updateOrCreate per pengajuan.
        // Kolom (cuti_id, kriteria_id) sudah unik di tabel penilaian_cutis.
        PenilaianCuti::upsert($rows, ['cuti_id', 'kriteria_id'], ['nilai', 'updated_at']);
    }

    /**
     * Ambil nilai C5 dari beban_kerja_units.
     */
    public function bebanKerja(int $unitKerjaId, string $periode): float
    {
        $nilai = BebanKerjaUnit::query()
            ->where('unit_kerja_id', $unitKerjaId)
            ->where('periode', $periode)
            ->value('nilai');

        // Fallback: cost terburuk jika admin belum mengisi beban kerja.
        return $nilai !== null ? (float) $nilai : 100.0;
    }

    /**
     * Validasi total bobot = 1.0000 (toleransi 0.0001).
     *
     * @param  Collection<int, mixed>  $kriteria
     *
     * @throws InvalidArgumentException
     */
    public function validateBobot(Collection $kriteria): void
    {
        $total = $kriteria->sum(fn ($item) => (float) data_get($item, 'bobot'));

        if (abs($total - 1.0) > 0.0001) {
            throw new InvalidArgumentException(
                "Total bobot kriteria harus 1.0000, saat ini {$total}."
            );
        }
    }

    /**
     * Ambil rincian normalisasi & kontribusi untuk satu cuti (untuk detail UI).
     *
     * @return array<int, array{kode:string, nama:string, tipe:string, nilai:float, bobot:float, normalisasi:float, kontribusi:float}>
     */
    public function rincian(Cuti $cuti): array
    {
        $kriteria = Kriteria::query()->orderBy('kode')->get();
        $penilaian = $cuti->penilaianCutis()
            ->with('kriteria')
            ->get()
            ->keyBy(fn (PenilaianCuti $p) => $p->kriteria->kode);

        if ($penilaian->isEmpty()) {
            return [];
        }

        // Normalisasi harus memakai max/min seluruh alternatif, bukan satu cuti.
        $allPenilaian = PenilaianCuti::query()
            ->whereIn('cuti_id', Cuti::query()
                ->where('periode', $cuti->periode)
                ->whereIn('status_global', Cuti::STATUS_PENDING)
                ->select('id'))
            ->get();

        $rows = [];

        foreach ($kriteria as $k) {
            $nilai = (float) ($penilaian[$k->kode]?->nilai ?? 0);
            $semuaNilai = $allPenilaian
                ->filter(fn (PenilaianCuti $p) => $p->kriteria_id === $k->id)
                ->pluck('nilai')
                ->map(fn ($v) => (float) $v);

            $maxNilai = $semuaNilai->max() ?: 0;
            $minNilai = $semuaNilai->min() ?: 0;

            if ($k->tipe === 'benefit') {
                $normalisasi = $maxNilai > 0 ? $nilai / $maxNilai : 0.0;
            } else {
                $normalisasi = $nilai > 0
                    ? $minNilai / $nilai
                    : ($minNilai == 0 ? 1.0 : 0.0);
            }

            $rows[] = [
                'kode'         => $k->kode,
                'nama'         => $k->nama_kriteria,
                'tipe'         => $k->tipe,
                'nilai'        => $nilai,
                'bobot'        => (float) $k->bobot,
                'normalisasi'  => $normalisasi,
                'kontribusi'   => $normalisasi * (float) $k->bobot,
            ];
        }

        return $rows;
    }
}
