<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Cuti;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Employee;
use Filament\Forms\Form;
use App\Models\UnitKerja;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\FormCutiGenerator;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Fieldset;

// === Komponen Form ===use Filament\Forms\Components\Section;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;

// === Komponen Tabel ===
use Filament\Tables\Columns\TextColumn;
use App\Notifications\CutiStatusUpdated;

// === Komponen Aksi (v3) ===
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;

// === Impor untuk EKSPOR (BARU) ===
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\CutiResource\Pages;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Filament\Resources\CutiResource\Pages\EditCuti;
use App\Filament\Resources\CutiResource\Pages\ListCutis;
use App\Filament\Resources\CutiResource\Pages\CreateCuti;


class CutiResource extends Resource
{
    protected static ?string $model = Cuti::class;

    // Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $modelLabel = 'Pengajuan Cuti';
    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';
    protected static ?int $navigationSort = 2;

    
    /**
     * Form Pengajuan Cuti (Logika Tahap 3 yang Benar)
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->label('Nama Pegawai')
                    ->relationship('employee', 'nama')
                    ->searchable()
                    // Logika 'required' dan 'visible' ini yang benar (bukan 'default' atau 'disabled')
                    ->required(fn () => Auth::user()->role === 'admin')
                    ->visible(fn () => Auth::user()->role === 'admin'),

                Select::make('jenis_cuti')
                    ->label('Jenis Cuti yang Diambil')
                    ->options([
                        'Cuti Tahunan' => 'Cuti Tahunan',
                        'Cuti Besar' => 'Cuti Besar',
                        'Cuti Sakit' => 'Cuti Sakit',
                        'Cuti Melahirkan' => 'Cuti Melahirkan',
                        'Cuti Alasan Penting' => 'Cuti Alasan Penting',
                        'Cuti Luar Tanggungan' => 'Cuti di Luar Tanggungan Negara',
                    ])
                    ->required(),

                DatePicker::make('tanggal_mulai')
                    ->required()
                    ->native(false),
                DatePicker::make('tanggal_akhir')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('tanggal_mulai'),

                Textarea::make('alasan')
                    ->label('Alasan Cuti')
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('alamat_selama_cuti')
                    ->label('Alamat Selama Menjalankan Cuti')
                    ->required()
                    ->columnSpanFull()

                // === TAMBAHKAN INPUT INI ===
                Forms\Components\TextInput::make('lampiran_link')
                    ->label('Bukti Lampiran (Link Google Drive / Lainnya)')
                    ->placeholder('https://drive.google.com/file/d/...')
                    ->url() // Validasi format URL (harus http/https)
                    ->suffixIcon('heroicon-m-link')
                    ->required(fn (Get $get) => $get('jenis_cuti') === 'Cuti Sakit') // ✅ Wajib jika Cuti Saki
                    ->helperText('Opsional. Wajib diisi (Link Surat Dokter) jika mengambil Cuti Sakit.')
                    ->columnSpanFull(), // Agar rapi memenuhi lebar
                // ===========================
            ]);
    }

    /**
     * Tabel List Cuti (dengan Aksi yang Sudah Digabung)
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.nama')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => Auth::user()->role !== 'pegawai'),

                TextColumn::make('jenis_cuti')
                    ->searchable(),
                
                TextColumn::make('tanggal_mulai')
                    ->date()
                    ->sortable(),

                TextColumn::make('tanggal_akhir')
                    ->date()
                    ->sortable(),
                
                BadgeColumn::make('status_global')
                    ->label('Status')
                    ->colors([
                        'warning' => fn ($state) => $state !== 'Disetujui' && $state !== 'Ditolak',
                        'success' => 'Disetujui',
                        'danger' => fn ($state) => str_contains($state, 'Ditolak'),
                    ]),
            ])
            ->filters([
                //
            ])
            // === SEMUA AKSI DIGABUNG DI SINI ===
        ->actions([
            
            // === AKSI PDF (MANUAL TCPDF) ===
            Action::make('exportFormPdf')
                ->label('Cetak Form PDF')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->action(function (Cuti $record) {
                    // Buat response streaming
                    return new StreamedResponse(function () use ($record) {
                        // Panggil generator kita
                        // Generator akan otomatis membuat dan mengeluarkan (output) PDF
                        app(FormCutiGenerator::class)->generate($record);
                    }, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="form_cuti.pdf"',
                    ]);
                }),

            // === AKSI TAHAP 1: KETUA TIM / ATASAN LANGSUNG ===
            Action::make('Proses Atasan Langsung')
                ->label('Proses (Ketua Unit)')
                ->icon('heroicon-o-pencil')
                ->color('info')
                ->visible(function (Cuti $record) {
                    $user = Auth::user();
                    // Cek apakah user adalah Ketua Unit dari pegawai yang mengajukan
                    $isKetuaUnit = $record->employee->unitKerja && 
                                   $record->employee->unitKerja->ketua_user_id === $user->id;
                    
                    return $isKetuaUnit && $record->status_global === 'Menunggu Persetujuan Atasan Langsung';
                })
                ->form([
                    // PERBAIKAN 1: Ganti 'status_sdm' jadi 'status_atasan_langsung'
                    Select::make('status_atasan_langsung')
                        ->label('Persetujuan Ketua Unit')
                        ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                        ->required(),
                    // PERBAIKAN 2: Ganti 'tanggapan_sdm' jadi 'tanggapan_atasan_langsung'
                    Textarea::make('tanggapan_atasan_langsung')
                        ->label('Catatan / Tanggapan'),
                ])
                ->action(function (Cuti $record, array $data): void {
                    // PERBAIKAN 3: Simpan ke kolom yang benar
                    $record->status_atasan_langsung = $data['status_atasan_langsung'];
                    $record->tanggapan_atasan_langsung = $data['tanggapan_atasan_langsung'];
                    $record->atasan_langsung_approver_id = Auth::id();

                    if ($data['status_atasan_langsung'] === 'approved') {
                        $record->status_global = 'Menunggu Persetujuan Tata Usaha';
                    } else {
                        // Opsional: Ambil nama unit agar pesan lebih jelas
                        $namaUnit = $record->employee->unitKerja->nama ?? 'Unit';
                        $record->status_global = "Ditolak (oleh Ketua $namaUnit)";
                    }
                    $record->save();
                }),

            // === AKSI TAHAP 2: KASUBBAG TATA USAHA ===
            Action::make('Proses Tata Usaha')
                ->icon('heroicon-o-pencil')
                ->color('info')
                ->visible(function (Cuti $record) {
                    return Auth::user()->role === 'tata_usaha' && $record->status_global === 'Menunggu Persetujuan Tata Usaha';
                })
                ->form([
                    Select::make('status_tata_usaha')
                        ->label('Persetujuan Kasubbag Tata Usaha')
                        ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                        ->required(),
                    Textarea::make('tanggapan_tata_usaha')
                        ->label('Catatan / Tanggapan'),
                ])
                ->action(function (Cuti $record, array $data): void {
                    $record->status_tata_usaha = $data['status_tata_usaha'];
                    $record->tanggapan_tata_usaha = $data['tanggapan_tata_usaha'];
                    $record->tata_usaha_approver_id = Auth::id();

<<<<<<< HEAD
                        if ($data['status_tata_usaha'] === 'approved') {
                            $record->status_global = 'Menunggu Persetujuan Kepala';
                        } else {
                            $record->status_global = 'Ditolak (oleh Tata Usaha)';
                        }
                        $record->save();
                    }),
                
                // === AKSI TAHAP 3: KEPALA STASIUN  ===
                Action::make('Proses Atasan Langsung')
                    ->label('Proses (Ketua Unit)')
                    ->icon('heroicon-o-pencil') // Tambahkan ikon
                    ->color('info')
                    ->visible(function (Cuti $record) {
                        $user = Auth::user();
                        
                        // Hanya muncul jika User adalah Ketua dari Unit si Pegawai
                        // DAN Statusnya 'Menunggu Persetujuan Atasan Langsung'
                        $isKetuaUnit = $record->employee->unitKerja 
                                    && $record->employee->unitKerja->ketua_user_id === $user->id;
                                    
                        return $isKetuaUnit && $record->status_global === 'Menunggu Persetujuan Atasan Langsung';
                    })
                    ->form([
                        // Update nama field ke 'status_atasan_langsung'
                        Select::make('status_atasan_langsung')
                            ->label('Persetujuan Ketua Unit')
                            ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                            ->required(),
                        Textarea::make('tanggapan_atasan_langsung')
                            ->label('Catatan / Tanggapan'),
                    ])
                    ->action(function (Cuti $record, array $data): void {
                        $record->status_atasan_langsung = $data['status_atasan_langsung'];
                        $record->tanggapan_atasan_langsung = $data['tanggapan_atasan_langsung'];
                        $record->atasan_langsung_approver_id = Auth::id();
                        $employee = $record->employee;

                        if ($data['status_kepala'] === 'approved') {
                            $durasiCuti = Carbon::parse($record->tanggal_mulai)
                                              ->diffInDays(Carbon::parse($record->tanggal_akhir)) + 1;
                            
                            if ($record->jenis_cuti === 'Cuti Tahunan') {
                                if ($employee->sisa_cuti_tahunan < $durasiCuti) {
                                    Notification::make()
                                        ->title('Gagal! Sisa Cuti Tahunan tidak mencukupi.')
                                        ->body("Pegawai {$employee->nama} hanya memiliki {$employee->sisa_cuti_tahunan} hari.")
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                $employee->sisa_cuti_tahunan -= $durasiCuti;
                                $employee->save();
                            }
                            $record->status_global = 'Disetujui';
                        } else {
                            $record->status_global = 'Ditolak (oleh Kepala)';
                        }
                        $record->save();
                        $employee->notify(new CutiStatusUpdated($record));
                    }),
                
                // Tombol default
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
=======
                    if ($data['status_tata_usaha'] === 'approved') {
                        $record->status_global = 'Menunggu Persetujuan Kepala';
                    } else {
                        $record->status_global = 'Ditolak (oleh Tata Usaha)';
                    }
                    $record->save();
                }),

            // === AKSI TAHAP 3: KEPALA STASIUN ===
            Action::make('Proses Kepala Stasiun')
                ->icon('heroicon-o-pencil')
                ->color('info')
                ->visible(function (Cuti $record) {
                    return Auth::user()->role === 'kepala_stasiun' 
                        && $record->status_global === 'Menunggu Persetujuan Kepala';
                })
                ->form([
                    Select::make('status_kepala')
                        ->label('Persetujuan Kepala Stasiun')
                        ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                        ->required(),
                    Textarea::make('tanggapan_kepala')
                        ->label('Catatan / Tanggapan'),
                ])
                ->action(function (Cuti $record, array $data): void {
                    $record->status_kepala = $data['status_kepala'];
                    $record->tanggapan_kepala = $data['tanggapan_kepala'];
                    $record->kepala_stasiun_approver_id = Auth::id();

                    if ($data['status_kepala'] === 'approved') {
                        $record->status_global = 'Disetujui';
                    } else {
                        $record->status_global = 'Ditolak (oleh Kepala Stasiun)';
                    }
                    $record->save();
                }),

            // Tombol default
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
>>>>>>> 37f2227641ba1d2e2608a09645804310d9cccce1
    }

    /**
     * "OTAK" FILTER (Logika BARU)
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        switch ($user->role) {
            case 'pegawai':
                if (!$user->employee) { return $query->where('id', 0); }
                return $query->where('employee_id', $user->employee->id);
            
            // GANTI CASE 'sdm' MENJADI 'ketua_tim'
            case 'ketua_tim': 
                $unitKerja = \App\Models\UnitKerja::where('ketua_user_id', $user->id)->first();
                
                if ($unitKerja) {
                    return $query->whereHas('employee', function ($q) use ($unitKerja) {
                        $q->where('unit_kerja_id', $unitKerja->id);
                    })->where('status_global', 'Menunggu Persetujuan Atasan Langsung');
                }
                return $query->where('id', 0); // ❌ Jika tidak ada unit, return empty

            case 'tata_usaha':
                return $query->where('status_global', 'Menunggu Persetujuan Tata Usaha');
            
            case 'kepala_stasiun':
                return $query->where('status_global', 'Menunggu Persetujuan Kepala');

            case 'admin':
                return $query;
            
            default:
                return $query->where('id', 0);
        }
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCutis::route('/'),
            'create' => Pages\CreateCuti::route('/create'),
            'edit' => Pages\EditCuti::route('/{record}/edit'),
        ];
    }
}