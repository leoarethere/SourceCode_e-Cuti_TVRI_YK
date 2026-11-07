<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables; // <-- Penting: untuk query User di Select
use Filament\Forms\Form;
use App\Models\UnitKerja;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;

// === IMPOR KOMPONEN FORM & TABEL ===
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\UnitKerjaResource\Pages;

class UnitKerjaResource extends Resource
{
    protected static ?string $model = UnitKerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 2;

    // === LABEL KUSTOM (Menghilangkan 's') ===
    protected static ?string $modelLabel = 'Unit Kerja';
    protected static ?string $pluralModelLabel = 'Unit Kerja';
    protected static ?string $navigationLabel = 'Unit Kerja';
    // ======================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Unit Kerja')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Penunjukan ketua unit. Satu user hanya boleh menjadi ketua
                // satu unit (rule unique), agar perpindahan/penggandaan ketua
                // tidak membuat approval tahap 1 ambigu.
                Forms\Components\Select::make('ketua_user_id')
                    ->label('Ketua Unit')
                    ->relationship('ketua', 'name')
                    ->options(fn () => User::query()
                        ->whereIn('role', ['ketua_tim', 'pegawai'])
                        ->orderBy('name')
                        ->get()
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText('Pegawai yang menyetujui pengajuan cuti tahap pertama untuk unit ini. Satu user hanya dapat menjadi ketua satu unit.')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->whereNotNull('ketua_user_id'),
                    )
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Unit Kerja')
                    ->searchable(),

                // Menggunakan relasi 'ketua' untuk menampilkan 'name' user
                TextColumn::make('ketua.name')
                    ->label('Ketua Unit')
                    ->placeholder('Belum ada ketua')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitKerjas::route('/'),
            'create' => Pages\CreateUnitKerja::route('/create'),
            'edit' => Pages\EditUnitKerja::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    // Master unit kerja: admin only (block URL langsung juga)
    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function canEdit($record): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function canDelete($record): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }
}