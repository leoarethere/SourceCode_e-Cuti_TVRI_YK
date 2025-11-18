<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\UnitKerja;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
// Pastikan Anda menambahkan 'use' statement ini di atas
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers; // Untuk Hashing password

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                
                // === INI PERUBAHANNYA ===
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'kepala_stasiun' => 'Kepala Stasiun',
                        'tata_usaha' => 'Kasubbag Tata Usaha',
                        'ketua_tim' => 'Ketua Tim / Unit',
                        'pegawai' => 'Pegawai',
                        // 'sdm' => 'Ketua Tim SDM',
                        
                        
                    ])
                    ->required()
                    ->default('pegawai')
                    ->reactive(),

                    Select::make('unit_kerja_id')
                        ->label('Unit Kerja')
                        ->options(UnitKerja::all()->pluck('nama', 'id'))
                        ->searchable()
                        ->visible(fn ($get) => in_array($get('role'), ['pegawai', 'ketua_tim'])), // Hanya muncul jika role Pegawai/Ketua
                // ========================

                // === TAMBAHKAN BLOK INI ===
                FileUpload::make('signature_image_path')
                    ->label('Upload Tanda Tangan (PNG)')
                    ->image()
                    ->imageEditor() // Izinkan user crop/rotate
                    ->directory('signatures') // Simpan di 'storage/app/public/signatures'
                    ->preserveFilenames()
                    ->nullable()
                    // Hanya tampilkan field ini jika rolenya adalah atasan
                    ->visible(fn ($get) => in_array($get('role'), ['sdm', 'tata_usaha', 'kepala_stasiun'])),
                // =========================

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Hanya tampilkan menu ini jika role user adalah 'admin'
        return Auth::user()->role === 'admin';
    }
}
