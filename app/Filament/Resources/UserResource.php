<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\UnitKerja;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
// Pastikan Anda menambahkan 'use' statement ini di atas
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
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
                        'admin'          => 'Admin',
                        'kepala_stasiun' => 'Kepala Stasiun',
                        'tata_usaha'     => 'Kasubbag Tata Usaha',
                        'ketua_tim'      => 'Ketua Tim / Unit',
                        'pegawai'        => 'Pegawai',
                    ])
                    ->required()
                    ->default('pegawai')
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state === 'ketua_tim' && !$get('unit_kerja_id')) {
                            Notification::make()
                                ->title('Peringatan')
                                ->body('Role Ketua Tim harus memilih Unit Kerja')
                                ->warning()
                                ->send();
                        }
                    }),

                // Hanya muncul jika role Pegawai / Ketua Tim
                Select::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->options(UnitKerja::all()->pluck('nama', 'id'))
                    ->searchable()
                    ->required(fn ($get) => in_array($get('role'), ['pegawai', 'ketua_tim']))
                    ->visible(fn ($get) => in_array($get('role'), ['pegawai', 'ketua_tim'])),

                // Hanya muncul jika role memiliki TTD (ketua_tim, tata_usaha, kepala_stasiun)
                FileUpload::make('signature_image_path')
                    ->label('Upload Tanda Tangan (PNG)')
                    ->image()
                    ->maxSize(2048)
                    ->directory('signatures')
                    ->preserveFilenames()
                    ->visible(fn ($get) => in_array($get('role'), ['ketua_tim', 'tata_usaha', 'kepala_stasiun'])),

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
        return Auth::user()?->role === 'admin';
    }

    // Block akses URL langsung (nav-only tidak cukup)
    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
