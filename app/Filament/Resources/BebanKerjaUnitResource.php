<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BebanKerjaUnitResource\Pages;
use App\Models\BebanKerjaUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BebanKerjaUnitResource extends Resource
{
    protected static ?string $model = BebanKerjaUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'SPK';
    protected static ?string $navigationLabel = 'Beban Kerja Divisi';
    protected static ?string $modelLabel = 'Beban Kerja Divisi';
    protected static ?string $pluralModelLabel = 'Beban Kerja Divisi';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('unit_kerja_id')
                ->label('Divisi / Unit Kerja')
                ->relationship('unitKerja', 'nama')
                ->searchable()
                ->required(),

            TextInput::make('periode')
                ->label('Periode')
                ->required()
                ->maxLength(7)
                ->placeholder('2026-10')
                ->regex('/^\d{4}-(0[1-9]|1[0-2])$/')
                ->helperText('Format YYYY-MM, contoh 2026-10.'),

            TextInput::make('nilai')
                ->label('Persentase Beban Kerja')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(100)
                ->suffix('%')
                ->helperText('Semakin tinggi % beban kerja divisi, semakin kecil prioritas cuti (kriteria cost C5). Nilai 100 = beban penuh.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unitKerja.nama')
                    ->label('Divisi / Unit Kerja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('nilai')
                    ->label('Beban Kerja')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.') . '%')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        (float) $state >= 80 => 'danger',
                        (float) $state >= 60 => 'warning',
                        default               => 'success',
                    })
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('periode', 'desc')
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('periode')
                            ->label('Periode (YYYY-MM)')
                            ->placeholder(now()->format('Y-m')),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['periode'] ?? null, fn ($q, $p) => $q->where('periode', $p))
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBebanKerjaUnits::route('/'),
            'create' => Pages\CreateBebanKerjaUnit::route('/create'),
            'edit' => Pages\EditBebanKerjaUnit::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha'], true);
    }

    public static function canEdit($record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha'], true);
    }

    public static function canDelete($record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha'], true);
    }
}
