<x-filament-panels::page>
    {{-- CSS lokal: tidak bergantung pada kelas Tailwind yang harus dikompilasi --}}
    <style>
        .saw-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 1rem;
        }

        .saw-field-periode { width: 12rem; }
        .saw-field-unit    { width: 16rem; }
        .saw-action        { margin-left: auto; }

        .saw-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25rem;
            color: rgb(var(--gray-700));
        }

        .saw-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid rgb(var(--gray-200));
        }

        .saw-legend-title {
            margin-right: 0.25rem;
            font-size: 0.8125rem;
            color: rgb(var(--gray-500));
        }

        .saw-note {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .saw-note + .saw-note { margin-top: 0.375rem; }

        .dark .saw-label        { color: rgb(var(--gray-200)); }
        .dark .saw-legend       { border-top-color: rgba(255, 255, 255, 0.1); }
        .dark .saw-legend-title { color: rgb(var(--gray-400)); }

        /* Mobile: semua kontrol selebar layar */
        @media (max-width: 640px) {
            .saw-field-periode,
            .saw-field-unit,
            .saw-action { width: 100%; margin-left: 0; }

            .saw-action .fi-btn { width: 100%; }
        }
    </style>

    {{-- ─── PANEL FILTER, AKSI & LEGENDA ────────────────────────────────────── --}}
    <x-filament::section>
        <div class="saw-filters">
            <div class="saw-field-periode">
                <label for="periode" class="saw-label">Periode cuti</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="periode"
                        type="month"
                        wire:model.live="periode"
                    />
                </x-filament::input.wrapper>
            </div>

            @if (auth()->user()?->role !== 'ketua_tim')
                <div class="saw-field-unit">
                    <label for="unitKerjaId" class="saw-label">Divisi / unit kerja</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="unitKerjaId" wire:model.live="unitKerjaId">
                            <option value="">Semua divisi</option>
                            @foreach (\App\Models\UnitKerja::query()->orderBy('nama')->get() as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div class="saw-action">
                <x-filament::button
                    wire:click="hitung"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-calculator"
                    color="primary"
                >
                    <span wire:loading.remove wire:target="hitung">Hitung prioritas SAW</span>
                    <span wire:loading wire:target="hitung">Menghitung...</span>
                </x-filament::button>
            </div>
        </div>

        <div class="saw-legend">
            <span class="saw-legend-title">Keterangan rekomendasi</span>
            <x-filament::badge color="success">Rank 1: Prioritas utama</x-filament::badge>
            <x-filament::badge color="info">Rank 2: Direkomendasikan</x-filament::badge>
            <x-filament::badge color="warning">Rank 3: Pertimbangan</x-filament::badge>
            <x-filament::badge color="gray">Rank &ge; 4: Ditunda</x-filament::badge>
        </div>
    </x-filament::section>

    {{-- ─── PERINGATAN BEBAN KERJA ──────────────────────────────────────────── --}}
    @if ($missingBebanKerja > 0)
        <x-filament::section
            icon="heroicon-o-exclamation-triangle"
            icon-color="warning"
            heading="Beban kerja divisi belum lengkap"
        >
            <p class="saw-note">
                Ada <strong>{{ $missingBebanKerja }} divisi</strong> yang belum memiliki data beban kerja
                untuk periode <strong>{{ $periode }}</strong>. Nilai C5 pada divisi tersebut memakai
                fallback 100% (cost maksimal, prioritas rendah).
            </p>
            <p class="saw-note">
                Lengkapi lewat menu <strong>SPK &rarr; Beban Kerja Divisi</strong>.
            </p>
        </x-filament::section>
    @endif

    {{-- ─── TABEL ───────────────────────────────────────────────────────────── --}}
    {{ $this->table }}
</x-filament-panels::page>