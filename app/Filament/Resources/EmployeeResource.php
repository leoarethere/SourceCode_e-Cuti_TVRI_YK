<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;   // <-- Tambahkan ini

use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\EmployeeResource\Pages;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    // Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $modelLabel = 'Pegawai';
    protected static ?string $pluralModelLabel = 'Pegawai';
    protected static ?int $navigationSort = 1;

    /**
     * Form untuk Create/Edit Pegawai
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Pegawai')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true), 

                Select::make('user_id')
                    ->label('Akun Login (User)')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Pilih akun login yang terhubung ke pegawai ini.'),

                TextInput::make('NIP') 
                    ->label('NIP (Nomor Induk Pegawai)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('jabatan')
                    ->label('Jabatan')
                    ->required()
                    ->maxLength(255),

                // === KOLOM BARU DARI FORM PDF ===
                Select::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama')
                    ->required(),
                // ================================

                DatePicker::make('tanggal_bergabung')
                    ->label('Tanggal Bergabung (untuk hitung Masa Kerja)')
                    ->required()
                    ->native(false), // Tampilan kalender modern

                TextInput::make('telp')
                    ->label('Nomor Telepon')
                    ->tel() // Tipe input telepon
                    ->maxLength(255),

                Textarea::make('alamat_domisili')
                    ->label('Alamat Domisili')
                    ->columnSpanFull(), // Lebar penuh
                // ================================

                // === NAMA KOLOM DIPERBARUI ===
                TextInput::make('sisa_cuti_tahunan')
                    ->label('Sisa Cuti Tahunan')
                    ->required()
                    ->numeric()
                    ->default(12)
                    ->minLength(0),
            ]);
    }

    /**
     * Tabel List Pegawai
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('NIP')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sisa_cuti_tahunan')
                    ->label('Sisa Cuti')
                    ->sortable(),
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
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $userRole = Auth::user()?->role;

        // PERBAIKAN: Izinkan 'tata_usaha' melihat menu pegawai juga
        // Hapus 'sdm' jika memang role itu sudah tidak dipakai
        return in_array($userRole, ['admin', 'tata_usaha']);
    }

    // Block akses URL langsung (nav-only tidak cukup)
    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha']);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha']);
    }

    public static function canEdit($record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha']);
    }

    public static function canDelete($record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'tata_usaha']);
    }
}