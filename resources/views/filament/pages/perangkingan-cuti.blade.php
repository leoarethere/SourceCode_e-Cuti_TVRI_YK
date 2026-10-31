<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-48">
                    <label for="periode" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                        Periode Cuti
                    </label>

                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="periode"
                            type="month"
                            wire:model.live="periode"
                        />
                    </x-filament::input.wrapper>
                </div>

                @if (auth()->user()?->role !== 'ketua_tim')
                    <div class="w-full sm:w-64">
                        <label for="unitKerjaId" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            Filter Divisi / Unit Kerja
                        </label>

                        <x-filament::input.wrapper>
                            <x-filament::input.select id="unitKerjaId" wire:model.live="unitKerjaId">
                                <option value="">Semua Divisi</option>
                                @foreach (\App\Models\UnitKerja::query()->orderBy('nama')->get() as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif

                <div>
                    <x-filament::button
                        wire:click="hitung"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-calculator"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="hitung">Hitung Prioritas SAW</span>
                        <span wire:loading wire:target="hitung">Menghitung...</span>
                    </x-filament::button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-medium text-gray-500 dark:text-gray-400">Rekomendasi:</span>
                <span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">Rank 1: Prioritas Utama</span>
                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">Rank 2: Direkomendasikan</span>
                <span class="inline-flex items-center gap-1 rounded-md bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-300">Rank 3: Pertimbangan</span>
                <span class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400">Rank &ge; 4: Ditunda</span>
            </div>
        </div>
    </div>

    @if ($missingBebanKerja > 0)
        <div class="flex items-start gap-3 rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 shadow-sm dark:border-warning-700 dark:bg-warning-900/30 dark:text-warning-200">
            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400" />
            <div>
                <p class="font-semibold">Beban Kerja Divisi Belum Lengkap</p>
                <p class="mt-0.5">
                    Ada <strong>{{ $missingBebanKerja }} divisi</strong> yang belum memiliki data beban kerja untuk periode <strong>{{ $periode }}</strong>.
                    Nilai C5 pada divisi tersebut akan menggunakan fallback 100% (cost maksimal/prioritas rendah).
                    Lengkapi nilai beban kerja melalui menu <strong>SPK &rarr; Beban Kerja Divisi</strong>.
                </p>
            </div>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
