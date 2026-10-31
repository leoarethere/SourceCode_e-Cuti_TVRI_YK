<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KriteriaResource\Pages;
use App\Models\Kriteria;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class KriteriaResource extends Resource
{
    protected static ?string $model = Kriteria::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'SPK';
    protected static ?string $navigationLabel = 'Kriteria SAW';
    protected static ?string $modelLabel = 'Kriteria';
    protected static ?string $pluralModelLabel = 'Kriteria';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('kode')
                ->label('Kode')
                ->required()
                ->maxLength(5)
                ->unique(ignoreRecord: true)
                ->disabledOn('edit')
                ->dehydrated()
                ->placeholder('C1'),

            TextInput::make('nama_kriteria')
                ->label('Nama Kriteria')
                ->required()
                ->maxLength(255),

            Select::make('tipe')
                ->label('Tipe')
                ->options([
                    'benefit' => 'Benefit',
                    'cost' => 'Cost',
                ])
                ->required(),

            TextInput::make('bobot')
                ->label('Bobot (0.00 – 1.00)')
                ->numeric()
                ->required()
                ->minValue(0)
                ->maxValue(1)
                ->step(0.01)
                ->suffix('desimal')
                ->helperText('Contoh: 0.30 = 30%. Mengubah satu bobot akan otomatis menyesuaikan bobot kriteria lain secara proporsional agar total tetap 1.00.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->sortable(),

                TextColumn::make('nama_kriteria')
                    ->label('Nama Kriteria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => $state === 'benefit' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('bobot')
                    ->label('Bobot')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.') . ' (' . number_format((float) $state * 100, 0) . '%)')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kode')
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
            'index' => Pages\ListKriterias::route('/'),
            'create' => Pages\CreateKriteria::route('/create'),
            'edit' => Pages\EditKriteria::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        // Lima kriteria SAW bersifat tetap agar konsisten dengan laporan.
        return false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete($record): bool
    {
        // Kriteria tidak boleh dihapus karena akan merusak matriks penilaian.
        return false;
    }
}
