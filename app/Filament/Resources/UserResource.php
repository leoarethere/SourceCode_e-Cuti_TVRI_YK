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
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';

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
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Peran (Role)')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin'          => 'danger',
                        'kepala_stasiun' => 'primary',
                        'tata_usaha'     => 'warning',
                        'ketua_tim'      => 'info',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin'          => 'Admin',
                        'kepala_stasiun' => 'Kepala Stasiun',
                        'tata_usaha'     => 'Kasubbag Tata Usaha',
                        'ketua_tim'      => 'Ketua Tim / Unit',
                        'pegawai'        => 'Pegawai',
                        default          => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unitKerja.nama')
                    ->label('Unit Kerja')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('signature_image_path')
                    ->label('TTD Digital')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran (Role)')
                    ->options([
                        'admin'          => 'Admin',
                        'kepala_stasiun' => 'Kepala Stasiun',
                        'tata_usaha'     => 'Kasubbag Tata Usaha',
                        'ketua_tim'      => 'Ketua Tim / Unit',
                        'pegawai'        => 'Pegawai',
                    ]),
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
