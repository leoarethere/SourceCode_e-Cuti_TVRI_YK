<x-filament-panels::page>
    <div class="flex flex-wrap items-end gap-4">
        <div class="w-full sm:w-48">
            <label for="periode" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                Periode
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
                <label for="unitKerjaId" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    Divisi / Unit Kerja
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
            <x-filament::button wire:click="hitung" icon="heroicon-o-calculator">
                Hitung Prioritas
            </x-filament::button>
        </div>
    </div>

    @if ($missingBebanKerja > 0)
        <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-900/30 dark:text-warning-200">
            Ada {{ $missingBebanKerja }} divisi yang belum memiliki nilai beban kerja untuk periode
            <strong>{{ $periode }}</strong>. Nilai C5 akan memakai fallback 100 (prioritas terendah).
            Lengkapi melalui menu <strong>SPK &rarr; Beban Kerja Divisi</strong>.
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
