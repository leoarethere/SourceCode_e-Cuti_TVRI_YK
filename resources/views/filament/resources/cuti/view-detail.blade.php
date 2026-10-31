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
@endphp

<div class="space-y-5 p-1">

    {{-- ─── BAGIAN I: DATA PEGAWAI ──────────────────────────────────────────── --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
            I. Data Pegawai
        </h3>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Nama Pegawai</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $employee?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">NIP</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $employee?->NIP ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jabatan</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $employee?->jabatan ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Unit Kerja</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $unitKerja?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Sisa Cuti Tahunan</p>
                <p class="font-semibold text-blue-700 dark:text-blue-300">{{ $employee?->sisa_cuti_tahunan ?? '-' }} Hari</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal Pengajuan</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $record->created_at ? Carbon::parse($record->created_at)->isoFormat('D MMMM Y') : '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ─── BAGIAN II: DETAIL CUTI ──────────────────────────────────────────── --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
            II. Detail Pengajuan Cuti
        </h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 space-y-3">
            <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Jenis Cuti</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $record->jenis_cuti ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Hari</p>
                    <p class="font-semibold text-blue-700 dark:text-blue-300">{{ $record->lama_cuti }} Hari</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tingkat Urgensi (C4)</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $record->urgensi ?? '-' }} / 5</p>
                </div>
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tanggal Cuti</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $tanggalDetail }}</p>
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Alasan Cuti</p>
                <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ $record->alasan ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Alamat Selama Cuti</p>
                <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ $record->alamat_selama_cuti ?? '-' }}</p>
            </div>

            @if($record->lampiran_link)
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Lampiran</p>
                <a href="{{ $record->lampiran_link }}" target="_blank"
                   class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <x-heroicon-m-link class="w-4 h-4" />
                    Lihat Dokumen
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- ─── BAGIAN III: STATUS GLOBAL ───────────────────────────────────────── --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
            III. Status Pengajuan
        </h3>
        <div class="flex items-center gap-3 mb-4">
            <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColor }}">
                {{ $record->status_global ?? '-' }}
            </span>
        </div>

        {{-- Timeline Approval --}}
        <div class="space-y-3">
            @foreach($approvalSteps as $i => $step)
                @php
                    $st = $step['status'] ?? 'pending';
                    $badgeClass = match($st) {
                        'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        default    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                    };
                    $badgeLabel = match($st) {
                        'approved' => '✓ Disetujui',
                        'rejected' => '✗ Ditolak',
                        default    => '⏳ Menunggu',
                    };
                @endphp
                <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $step['label'] }}</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>
                        </div>
                        @if($step['approver'])
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Oleh: {{ $step['approver'] }}</p>
                        @endif
                        @if($step['catatan'])
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 italic">
                                "{{ $step['catatan'] }}"
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if(in_array($record->status_global, \App\Models\Cuti::STATUS_PENDING, true))
    @php
        $sawRows = app(\App\Services\SawService::class)->rincian($record);
        $sawTotal = collect($sawRows)->sum('kontribusi');
    @endphp
    {{-- ─── BAGIAN IV: RINCIAN PERHITUNGAN SAW ─────────────────────────────── --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
            IV. Rincian Perhitungan SAW
        </h3>

        @if(!empty($sawRows))
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 space-y-3">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ranking</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $record->ranking ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Skor Prioritas</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">
                            {{ $record->skor_prioritas !== null
                                ? number_format((float) $record->skor_prioritas, 3, ',', '.')
                                : number_format($sawTotal, 3, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Status Rekomendasi</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $record->status_rekomendasi }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-3">Kriteria</th>
                                <th class="py-2 pr-3 text-right">Nilai</th>
                                <th class="py-2 pr-3 text-right">Bobot</th>
                                <th class="py-2 pr-3 text-right">Normalisasi</th>
                                <th class="py-2 text-right">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($sawRows as $row)
                                <tr>
                                    <td class="py-2 pr-3">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $row['kode'] }} - {{ $row['nama'] }}
                                        </span>
                                        <span class="ml-1 text-xs text-gray-400">({{ $row['tipe'] }})</span>
                                    </td>
                                    <td class="py-2 pr-3 text-right">{{ number_format($row['nilai'], 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3 text-right">{{ number_format($row['bobot'], 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3 text-right">{{ number_format($row['normalisasi'], 3, ',', '.') }}</td>
                                    <td class="py-2 text-right font-medium">{{ number_format($row['kontribusi'], 3, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 dark:border-gray-600">
                                <td colspan="4" class="py-2 pr-3 text-right font-semibold text-gray-700 dark:text-gray-300">
                                    Total Skor Prioritas
                                </td>
                                <td class="py-2 text-right font-bold text-blue-700 dark:text-blue-300">
                                    {{ number_format($sawTotal, 3, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 text-sm text-gray-500 dark:text-gray-400">
                Rincian SAW belum tersedia. Buka menu <strong>SPK &rarr; Perangkingan SAW</strong>
                lalu klik <strong>Hitung Prioritas</strong> untuk periode {{ $record->periode ?? '-' }}.
            </div>
        @endif
    </div>
    @endif

</div>
