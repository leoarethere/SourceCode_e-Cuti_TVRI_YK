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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\EmployeeResource\Pages;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    // Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Pegawai';
    protected static ?string $pluralModelLabel = 'Pegawai';
    protected static ?string $recordTitleAttribute = 'nama';
    protected static ?int $navigationSort = 1;

    /**
     * Form untuk Create/Edit Pegawai
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identitas Pegawai')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('NIP')
                        ->label('NIP (Nomor Induk Pegawai)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email Pegawai')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('jabatan')
                        ->label('Jabatan')
                        ->required()
                        ->maxLength(255),

                    Select::make('unit_kerja_id')
                        ->label('Unit Kerja / Divisi')
                        ->relationship('unitKerja', 'nama')
                        ->searchable()
                        ->required(),

                    DatePicker::make('tanggal_bergabung')
                        ->label('Tanggal Bergabung')
                        ->required()
                        ->native(false)
                        ->helperText('Dipakai untuk menghitung masa kerja (C3 SAW).'),

                    TextInput::make('telp')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(255),

                    Textarea::make('alamat_domisili')
                        ->label('Alamat Domisili')
                        ->columnSpanFull(),
                ]),

            Section::make('Akun & Hak Akses')
                ->icon('heroicon-o-user-circle')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Akun Login (User)')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->nullable()
                        ->unique(ignoreRecord: true)
                        ->helperText('Pilih akun login yang terhubung ke pegawai ini.'),

                    TextInput::make('sisa_cuti_tahunan')
                        ->label('Sisa Cuti Tahunan (hari)')
                        ->required()
                        ->numeric()
                        ->default(12)
                        ->minValue(0)
                        ->suffix('hari'),
                ]),

            Section::make('Data Kinerja (SPK SAW)')
                ->icon('heroicon-o-chart-bar')
                ->columns(1)
                ->schema([
                    TextInput::make('nilai_kinerja')
                        ->label('Nilai Kinerja (0–100)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(75)
                        ->helperText('Dipakai sebagai kriteria C2 (Kinerja) pada perhitungan SAW.'),
                ]),
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

                TextColumn::make('unitKerja.nama')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('user.name')
                    ->label('Akun Login')
                    ->placeholder('Belum terhubung')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nilai_kinerja')
                    ->label('Kinerja')
                    ->sortable(),

                TextColumn::make('masa_kerja_tahun')
                    ->label('Masa Kerja')
                    ->formatStateUsing(fn ($state) => $state . ' tahun')
                    ->toggleable(),

                TextColumn::make('sisa_cuti_tahunan')
                    ->label('Sisa Cuti')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama')
                    ->searchable()
                    ->preload(),
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