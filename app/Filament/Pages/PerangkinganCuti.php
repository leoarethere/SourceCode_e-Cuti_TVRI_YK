<?php

namespace App\Filament\Pages;

use App\Models\BebanKerjaUnit;
use App\Models\Cuti;
use App\Models\UnitKerja;
use App\Services\SawService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PerangkinganCuti extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.perangkingan-cuti';
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'SPK';
    protected static ?string $navigationLabel = 'Perangkingan SAW';
    protected static ?string $title = 'Perangkingan Prioritas Cuti (SAW)';
    protected static ?int $navigationSort = 3;

    public string $periode = '';
    public ?int $unitKerjaId = null;

    public function mount(): void
    {
        $this->periode = now()->format('Y-m');

        if (Auth::user()?->role === 'ketua_tim') {
            $this->unitKerjaId = UnitKerja::query()
                ->where('ketua_user_id', Auth::id())
                ->value('id');
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Perangkingan Prioritas Cuti (SAW)';
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin',
            'tata_usaha',
            'kepala_stasiun',
            'ketua_tim',
        ], true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function updatedPeriode(): void
    {
        $this->resetTable();
    }

    public function updatedUnitKerjaId(): void
    {
        $this->resetTable();
    }

    public function hitung(): void
    {
        try {
            $hasil = app(SawService::class)->hitungPrioritas($this->periode);

            Notification::make()
                ->title('Perhitungan SAW selesai')
                ->body($hasil->isEmpty()
                    ? "Tidak ada pengajuan pending pada periode {$this->periode}."
                    : "{$hasil->count()} pengajuan berhasil diperingkatkan untuk periode {$this->periode}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Perhitungan SAW gagal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('ranking')
            ->columns([
                TextColumn::make('ranking')
                    ->label('Rank')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state === 1 => 'success',
                        (int) $state === 2 => 'info',
                        (int) $state === 3 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('employee.nama')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.unitKerja.nama')
                    ->label('Divisi')
                    ->toggleable(),

                TextColumn::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->toggleable(),

                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Cuti')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('urgensi')
                    ->label('C4 Urgensi')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('skor_prioritas')
                    ->label('Skor SAW')
                    ->formatStateUsing(fn ($state) => $state !== null
                        ? number_format((float) $state, 3, ',', '.')
                        : '-')
                    ->sortable(),

                TextColumn::make('status_rekomendasi')
                    ->label('Status Rekomendasi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Prioritas Utama' => 'success',
                        'Direkomendasikan' => 'info',
                        'Pertimbangan' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->emptyStateHeading('Belum ada data perangkingan')
            ->emptyStateDescription('Pilih periode lalu klik "Hitung Prioritas" untuk memproses pengajuan cuti yang masih pending.')
            ->emptyStateIcon('heroicon-o-trophy');
    }

    protected function getTableQuery(): Builder
    {
        $query = Cuti::query()
            ->with(['employee.unitKerja'])
            ->where('periode', $this->periode)
            ->whereIn('status_global', Cuti::STATUS_PENDING);

        if (Auth::user()?->role === 'ketua_tim') {
            $unitId = $this->unitKerjaId;

            if (! $unitId) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('employee', fn (Builder $q) => $q->where('unit_kerja_id', $unitId));
        } elseif ($this->unitKerjaId) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('unit_kerja_id', $this->unitKerjaId));
        }

        return $query->orderBy('ranking')->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $missingBebanKerja = UnitKerja::query()
            ->whereNotIn('id', BebanKerjaUnit::query()
                ->where('periode', $this->periode)
                ->select('unit_kerja_id'))
            ->count();

        return [
            'missingBebanKerja' => $missingBebanKerja,
        ];
    }
}
