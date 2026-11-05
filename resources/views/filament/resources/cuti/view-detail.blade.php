@php
    use Carbon\Carbon;

    $employee = $record->employee;
    $unitKerja = $employee?->unitKerja;

    // Format tanggal cuti
    if (!empty($record->tanggal_cuti_array) && is_array($record->tanggal_cuti_array)) {
        $dates = collect($record->tanggal_cuti_array)->sort()->values();
        $grouped = $dates
            ->map(fn($d) => Carbon::parse($d))
            ->groupBy(fn($d) => $d->format('Y-m'));

        $tanggalDetail = $grouped->map(function ($datesInMonth) {
            $monthYear = $datesInMonth->first()->isoFormat('MMMM Y');
            $days = $datesInMonth->map(fn($d) => $d->format('d'))->join(', ');
            return "$days $monthYear";
        })->join(' | ');
    } elseif ($record->tanggal_mulai && $record->tanggal_akhir) {
        $start = Carbon::parse($record->tanggal_mulai);
        $end   = Carbon::parse($record->tanggal_akhir);
        if ($start->isSameDay($end)) {
            $tanggalDetail = $start->isoFormat('D MMMM Y');
        } elseif ($start->isSameMonth($end)) {
            $tanggalDetail = $start->format('d') . ' - ' . $end->isoFormat('D MMMM Y');
        } else {
            $tanggalDetail = $start->isoFormat('D MMMM') . ' s/d ' . $end->isoFormat('D MMMM Y');
        }
    } else {
        $tanggalDetail = '-';
    }

    // Tiap potongan tanggal ditampilkan sebagai chip terpisah
    $tanggalChips = array_filter(array_map('trim', explode(' | ', $tanggalDetail)));

    $statusColor = match(true) {
        str_contains($record->status_global ?? '', 'Disetujui') => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
        str_contains($record->status_global ?? '', 'Ditolak')   => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30',
        default                                                  => 'text-yellow-700 bg-yellow-100 dark:text-yellow-300 dark:bg-yellow-900/30',
    };

    $approvalSteps = [
        [
            'label'   => 'Ketua Unit (' . ($unitKerja?->nama ?? '-') . ')',
            'status'  => $record->status_atasan_langsung,
            'catatan' => $record->tanggapan_atasan_langsung,
            'approver'=> $record->atasanLangsungApprover?->employee?->nama ?? $record->atasanLangsungApprover?->name,
        ],
        [
            'label'   => 'Kasubbag Tata Usaha',
            'status'  => $record->status_tata_usaha,
            'catatan' => $record->tanggapan_tata_usaha,
            'approver'=> $record->tataUsahaApprover?->employee?->nama ?? $record->tataUsahaApprover?->name,
        ],
        [
            'label'   => 'Kepala Stasiun',
            'status'  => $record->status_kepala,
            'catatan' => $record->tanggapan_kepala,
            'approver'=> $record->kepalaApprover?->employee?->nama ?? $record->kepalaApprover?->name,
        ],
    ];

    // Kelas yang dipakai berulang
    $card     = 'rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900';
    $secTitle = 'text-sm font-semibold text-gray-900 dark:text-white';
    $label    = 'text-xs text-gray-500 dark:text-gray-400';
    $value    = 'mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100';
@endphp

<div class="space-y-6 p-1">

    {{-- ─── RINGKASAN ATAS ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-lg font-semibold leading-tight text-gray-900 dark:text-white">
                {{ $employee?->nama ?? '-' }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $record->jenis_cuti ?? '-' }} &middot; {{ $record->lama_cuti }} hari
            </p>
        </div>
        <span class="shrink-0 rounded-full px-3 py-1 text-sm font-semibold {{ $statusColor }}">
            {{ $record->status_global ?? '-' }}
        </span>
    </div>

    {{-- ─── BAGIAN I: DATA PEGAWAI ──────────────────────────────────────────── --}}
    <section>
        <h3 class="{{ $secTitle }} mb-3">I. Data pegawai</h3>
        <dl class="{{ $card }} grid grid-cols-1 gap-x-6 gap-y-4 p-4 sm:grid-cols-2">
            <div>
                <dt class="{{ $label }}">Nama pegawai</dt>
                <dd class="{{ $value }}">{{ $employee?->nama ?? '-' }}</dd>
            </div>
            <div>
                <dt class="{{ $label }}">NIP</dt>
                <dd class="{{ $value }}">{{ $employee?->NIP ?? '-' }}</dd>
            </div>
            <div>
                <dt class="{{ $label }}">Jabatan</dt>
                <dd class="{{ $value }}">{{ $employee?->jabatan ?? '-' }}</dd>
            </div>
            <div>
                <dt class="{{ $label }}">Unit kerja</dt>
                <dd class="{{ $value }}">{{ $unitKerja?->nama ?? '-' }}</dd>
            </div>
            <div>
                <dt class="{{ $label }}">Sisa cuti tahunan</dt>
                <dd class="mt-0.5 text-sm font-semibold text-blue-700 dark:text-blue-300">
                    {{ $employee?->sisa_cuti_tahunan ?? '-' }} hari
                </dd>
            </div>
            <div>
                <dt class="{{ $label }}">Tanggal pengajuan</dt>
                <dd class="{{ $value }}">
                    {{ $record->created_at ? Carbon::parse($record->created_at)->isoFormat('D MMMM Y') : '-' }}
                </dd>
            </div>
        </dl>
    </section>

    {{-- ─── BAGIAN II: DETAIL CUTI ──────────────────────────────────────────── --}}
    <section>
        <h3 class="{{ $secTitle }} mb-3">II. Detail pengajuan cuti</h3>
        <div class="{{ $card }} divide-y divide-gray-200 dark:divide-gray-700">

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 p-4 sm:grid-cols-3">
                <div>
                    <dt class="{{ $label }}">Jenis cuti</dt>
                    <dd class="{{ $value }}">{{ $record->jenis_cuti ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="{{ $label }}">Total hari</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-blue-700 dark:text-blue-300">
                        {{ $record->lama_cuti }} hari
                    </dd>
                </div>
                <div>
                    <dt class="{{ $label }}">Tingkat urgensi (C4)</dt>
                    <dd class="{{ $value }}">{{ $record->urgensi ?? '-' }} / 5</dd>
                </div>
            </dl>

            <div class="p-4">
                <p class="{{ $label }}">Tanggal cuti</p>
                <div class="mt-1.5 flex flex-wrap gap-2">
                    @foreach($tanggalChips as $chip)
                        <span class="rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                            {{ $chip }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="p-4">
                <p class="{{ $label }}">Alasan cuti</p>
                <p class="mt-1 text-sm leading-relaxed text-gray-800 dark:text-gray-200">{{ $record->alasan ?? '-' }}</p>
            </div>

            <div class="p-4">
                <p class="{{ $label }}">Alamat selama cuti</p>
                <p class="mt-1 text-sm leading-relaxed text-gray-800 dark:text-gray-200">{{ $record->alamat_selama_cuti ?? '-' }}</p>
            </div>

            @if($record->lampiran_link)
                <div class="p-4">
                    <p class="{{ $label }}">Lampiran</p>
                    <a href="{{ $record->lampiran_link }}" target="_blank" rel="noopener noreferrer"
                       class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                        <x-heroicon-m-link class="h-4 w-4" />
                        Lihat dokumen
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- ─── BAGIAN III: STATUS PERSETUJUAN ──────────────────────────────────── --}}
    <section>
        <h3 class="{{ $secTitle }} mb-3">III. Status persetujuan</h3>
        <div class="{{ $card }} p-4">
            <ol>
                @foreach($approvalSteps as $step)
                    @php
                        $st = $step['status'] ?? 'pending';
                        $isLast = $loop->last;

                        $dotClass = match($st) {
                            'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            default    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        };
                        $badgeLabel = match($st) {
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default    => 'Menunggu',
                        };
                    @endphp

                    <li class="relative flex gap-3 {{ $isLast ? '' : 'pb-5' }}">
                        {{-- Garis penghubung antar tahap --}}
                        @unless($isLast)
                            <span class="absolute left-3 top-6 bottom-0 w-px bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                        @endunless

                        {{-- Ikon status --}}
                        <span class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $dotClass }}">
                            @if($st === 'approved')
                                <x-heroicon-m-check class="h-4 w-4" />
                            @elseif($st === 'rejected')
                                <x-heroicon-m-x-mark class="h-4 w-4" />
                            @else
                                <x-heroicon-m-clock class="h-4 w-4" />
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $step['label'] }}</p>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $dotClass }}">
                                    {{ $badgeLabel }}
                                </span>
                            </div>

                            @if($step['approver'])
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Oleh {{ $step['approver'] }}</p>
                            @endif

                            @if($step['catatan'])
                                <p class="mt-2 rounded-md border-l-2 border-gray-300 bg-gray-50 px-3 py-2 text-xs italic text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    &ldquo;{{ $step['catatan'] }}&rdquo;
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if(in_array($record->status_global, \App\Models\Cuti::STATUS_PENDING, true))
    @php
        $sawRows = app(\App\Services\SawService::class)->rincian($record);
        $sawTotal = collect($sawRows)->sum('kontribusi');
    @endphp

    {{-- ─── BAGIAN IV: RINCIAN PERHITUNGAN SAW ─────────────────────────────── --}}
    <section>
        <h3 class="{{ $secTitle }} mb-3">IV. Rincian perhitungan SAW</h3>

        @if(!empty($sawRows))
            <div class="space-y-3">
                {{-- Ringkasan hasil --}}
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="{{ $card }} p-4">
                        <dt class="{{ $label }}">Ranking</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $record->ranking ?? '-' }}
                        </dd>
                    </div>
                    <div class="{{ $card }} p-4">
                        <dt class="{{ $label }}">Skor prioritas</dt>
                        <dd class="mt-1 text-xl font-semibold text-blue-700 dark:text-blue-300">
                            {{ $record->skor_prioritas !== null
                                ? number_format((float) $record->skor_prioritas, 3, ',', '.')
                                : number_format($sawTotal, 3, ',', '.') }}
                        </dd>
                    </div>
                    <div class="{{ $card }} p-4">
                        <dt class="{{ $label }}">Status rekomendasi</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $record->status_rekomendasi ?? '-' }}
                        </dd>
                    </div>
                </dl>

                {{-- Tabel kriteria --}}
                <div class="{{ $card }} overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <th class="px-4 py-2.5 text-left font-medium">Kriteria</th>
                                <th class="px-3 py-2.5 text-right font-medium">Nilai</th>
                                <th class="px-3 py-2.5 text-right font-medium">Bobot</th>
                                <th class="px-3 py-2.5 text-right font-medium">Normalisasi</th>
                                <th class="px-4 py-2.5 text-right font-medium">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($sawRows as $row)
                                <tr class="text-gray-800 dark:text-gray-200">
                                    <td class="px-4 py-2.5">
                                        <span class="font-medium">{{ $row['kode'] }} - {{ $row['nama'] }}</span>
                                        <span class="ml-1 text-xs text-gray-400">({{ $row['tipe'] }})</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['nilai'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['bobot'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($row['normalisasi'], 3, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium tabular-nums">{{ number_format($row['kontribusi'], 3, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                                <td colspan="4" class="px-4 py-2.5 text-right font-semibold text-gray-700 dark:text-gray-300">
                                    Total skor prioritas
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold tabular-nums text-blue-700 dark:text-blue-300">
                                    {{ number_format($sawTotal, 3, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="{{ $card }} p-4 text-sm text-gray-500 dark:text-gray-400">
                Rincian SAW belum tersedia. Buka menu <strong>SPK &rarr; Perangkingan SAW</strong>
                lalu klik <strong>Hitung Prioritas</strong> untuk periode {{ $record->periode ?? '-' }}.
            </div>
        @endif
    </section>
    @endif

</div>