<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Cuti;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Employee;
use Filament\Forms\Form;
use App\Models\UnitKerja;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\FormCutiGenerator;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\CutiResource\Pages;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CutiResource extends Resource
{
    protected static ?string $model = Cuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Data Pengajuan Cuti';
    protected static ?string $modelLabel = 'Pengajuan Cuti';
    protected static ?string $pluralModelLabel = 'Data Pengajuan Cuti';
    protected static ?string $recordTitleAttribute = 'jenis_cuti';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengajuan Cuti')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Nama Pegawai')
                            ->relationship('employee', 'nama')
                            ->searchable()
                            ->required(fn () => Auth::user()->role === 'admin')
                            ->visible(fn () => Auth::user()->role === 'admin')
                            ->columnSpan(fn () => Auth::user()->role === 'admin' ? 1 : 2),

                        Select::make('jenis_cuti')
                            ->label('Jenis Cuti yang Diambil')
                            ->options([
                                'Cuti Tahunan' => 'Cuti Tahunan',
                                'Cuti Besar' => 'Cuti Besar',
                                'Cuti Sakit' => 'Cuti Sakit',
                                'Cuti Melahirkan' => 'Cuti Melahirkan',
                                'Cuti Alasan Penting' => 'Cuti Alasan Penting',
                                'Cuti Luar Tanggungan' => 'Cuti di Luar Tanggungan Negara',
                                'Cuti Tugas' => 'Cuti Tugas / Dinas Luar',
                                'Cuti Belajar' => 'Cuti / Tugas Belajar',
                                'Izin Lainnya' => 'Izin / Keterangan Lainnya',
                            ])
                            ->required()
                            ->columnSpan(fn () => Auth::user()->role === 'admin' ? 1 : 2),

                        Select::make('urgensi')
                            ->label('Tingkat Urgensi Cuti (Kriteria C4 SAW)')
                            ->options([
                                1 => '1 - Sangat Rendah (liburan / acara santai)',
                                2 => '2 - Rendah',
                                3 => '3 - Sedang (keperluan pribadi standar)',
                                4 => '4 - Tinggi (urusan keluarga penting)',
                                5 => '5 - Sangat Tinggi (sakit / musibah / mendesak)',
                            ])
                            ->default(3)
                            ->required()
                            ->helperText('Tingkat kepentingan cuti untuk pembobotan prioritas (SAW).')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pemilihan Tanggal Cuti')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\ViewField::make('tanggal_cuti_array')
                            ->label('Pilih Tanggal Cuti')
                            ->view('forms.components.calendar-checkbox')
                            ->required()
                            ->helperText('*Klik tanggal-tanggal yang Anda inginkan untuk cuti. Anda bisa memilih tanggal tidak berurutan.')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('tanggal_mulai')
                            ->default(fn ($get) => !empty($get('tanggal_cuti_array')) 
                                ? min($get('tanggal_cuti_array')) 
                                : null),
                        
                        Forms\Components\Hidden::make('tanggal_akhir')
                            ->default(fn ($get) => !empty($get('tanggal_cuti_array')) 
                                ? max($get('tanggal_cuti_array')) 
                                : null),
                    ]),

                Section::make('Keterangan & Lampiran')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Textarea::make('alasan')
                            ->label('Alasan Cuti')
                            ->placeholder('Jelaskan alasan atau keperluan cuti...')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('alamat_selama_cuti')
                            ->label('Alamat Selama Menjalankan Cuti')
                            ->placeholder('Alamat lengkap atau kota tujuan selama cuti...')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('lampiran_link')
                            ->label('Bukti Lampiran (Link Google Drive / Dokumen Pendukung)')
                            ->placeholder('https://drive.google.com/file/d/...')
                            ->url()
                            ->suffixIcon('heroicon-m-link')
                            ->required(fn (Get $get) => $get('jenis_cuti') === 'Cuti Sakit')
                            ->helperText('Opsional. Wajib diisi (misal: surat keterangan dokter) jika mengambil Cuti Sakit.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Override Status (Khusus Administrator)')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(false)
                    ->visible(fn () => Auth::user()->role === 'admin')
                    ->schema([
                        Select::make('status_global')
                            ->label('Status Pengajuan')
                            ->options([
                                Cuti::STATUS_MENUNGGU_ATASAN => Cuti::STATUS_MENUNGGU_ATASAN,
                                Cuti::STATUS_MENUNGGU_TU     => Cuti::STATUS_MENUNGGU_TU,
                                Cuti::STATUS_MENUNGGU_KEPALA => Cuti::STATUS_MENUNGGU_KEPALA,
                                Cuti::STATUS_DISETUJUI       => Cuti::STATUS_DISETUJUI,
                            ])
                            ->visible(fn () => Auth::user()->role === 'admin')
                            ->dehydrated(fn () => Auth::user()->role === 'admin')
                            ->helperText('Perhatian: Mengubah status di sini hanya mengubah status alur, tidak otomatis mengurangi/menambah saldo cuti tahunan.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.nama')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->employee?->unitKerja?->nama ?? '')
                    ->visible(fn () => Auth::user()->role !== 'pegawai'),
                    
                TextColumn::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->searchable(),
                    
                TextColumn::make('tanggal_mulai') // ✅ Ganti dari 'tanggal_cuti' ke 'tanggal_mulai'
                    ->label('Tanggal Cuti')
                    ->icon('heroicon-m-calendar-days') // Tambah ikon visual
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        // --- LOGIKA TAMPILAN UTAMA (RANGE TANGGAL) ---
                        
                        // Ambil data tanggal dari array (sistem baru) atau kolom biasa (sistem lama)
                        if (!empty($record->tanggal_cuti_array) && is_array($record->tanggal_cuti_array)) {
                            $dates = collect($record->tanggal_cuti_array)->sort()->values();
                            $start = $dates->first();
                            $end = $dates->last();
                        } else {
                            $start = $record->tanggal_mulai;
                            $end = $record->tanggal_akhir;
                        }

                        if (!$start) return '-';

                        $cStart = Carbon::parse($start);
                        $cEnd = $end ? Carbon::parse($end) : $cStart;

                        // Skenario 1: Cuti cuma 1 hari
                        if ($cStart->isSameDay($cEnd)) {
                            return $cStart->isoFormat('D MMM Y');
                        }

                        // Skenario 2: Range tanggal (Contoh: 10 - 12 Des 2025)
                        // Jika tahun sama
                        if ($cStart->isSameYear($cEnd)) {
                            // Jika bulan sama (10 - 12 Des 2025)
                            if ($cStart->isSameMonth($cEnd)) {
                                return $cStart->format('d') . ' - ' . $cEnd->isoFormat('D MMM Y'); 
                            }
                            // Jika bulan beda (30 Nov - 02 Des 2025)
                            return $cStart->isoFormat('D MMM') . ' - ' . $cEnd->isoFormat('D MMM Y');
                        }
                        
                        // Jika beda tahun
                        return $cStart->isoFormat('D MMM Y') . ' - ' . $cEnd->isoFormat('D MMM Y');
                    })
                    ->description(function ($record) {
                        // --- LOGIKA SUB-TEXT (DETAIL KECIL DI BAWAH TANGGAL) ---
                        
                        if (!empty($record->tanggal_cuti_array) && is_array($record->tanggal_cuti_array)) {
                            $count = count($record->tanggal_cuti_array);
                            
                            // Jika tanggalnya acak/lompat-lompat, beri info "Acak"
                            // Kita cek sederhana dengan membandingkan selisih hari vs jumlah item
                            $first = Carbon::parse(min($record->tanggal_cuti_array));
                            $last = Carbon::parse(max($record->tanggal_cuti_array));
                            $diffDays = $first->diffInDays($last) + 1;
                            
                            $status = ($diffDays != $count) ? '(Acak)' : '';

                            return "Total: {$count} Hari {$status}";
                        }
                        
                        return null; 
                    })
                    ->tooltip(function ($record) {
                        // --- TOOLTIP SAAT HOVER (Tampilkan semua tanggal lengkap) ---
                        if (!empty($record->tanggal_cuti_array) && is_array($record->tanggal_cuti_array)) {
                            return collect($record->tanggal_cuti_array)
                                ->sort()
                                ->map(fn($d) => Carbon::parse($d)->isoFormat('dddd, D MMMM Y'))
                                ->join("\n");
                        }
                        return null;
                    })
                    ->wrap(),

                // ✅ UPDATE: Hitung lama cuti dari array
                TextColumn::make('lama_cuti')
                    ->label('Lama')
                    ->formatStateUsing(function ($record) {
                        return $record->lama_cuti . ' Hari';
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('urgensi')
                    ->label('Urgensi')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state >= 5 => 'danger',
                        (int) $state >= 4 => 'warning',
                        (int) $state >= 3 => 'info',
                        default            => 'gray',
                    })
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ranking')
                    ->label('Rank')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state === 1 => 'success',
                        (int) $state === 2 => 'info',
                        (int) $state === 3 => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->visible(fn () => Auth::user()->role !== 'pegawai'),

                TextColumn::make('skor_prioritas')
                    ->label('Skor SAW')
                    ->formatStateUsing(fn ($state) => $state !== null
                        ? number_format((float) $state, 3, ',', '.')
                        : '-')
                    ->sortable()
                    ->visible(fn () => Auth::user()->role !== 'pegawai'),

                TextColumn::make('status_rekomendasi')
                    ->label('Rekomendasi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Prioritas Utama' => 'success',
                        'Direkomendasikan' => 'info',
                        'Pertimbangan' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn () => Auth::user()->role !== 'pegawai'),

                TextColumn::make('status_global')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_starts_with($state, Cuti::STATUS_DISETUJUI) => 'success',
                        str_starts_with($state, Cuti::STATUS_DITOLAK)   => 'danger',
                        default                            => 'warning',
                    })
                    ->icon(fn ($state) => match (true) {
                        str_starts_with($state, 'Menunggu')  => 'heroicon-m-clock',
                        str_starts_with($state, Cuti::STATUS_DISETUJUI) => 'heroicon-m-check-circle',
                        str_starts_with($state, Cuti::STATUS_DITOLAK)   => 'heroicon-m-x-circle',
                        default                            => null,
                    })
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->options([
                        'Cuti Tahunan' => 'Cuti Tahunan',
                        'Cuti Besar' => 'Cuti Besar',
                        'Cuti Sakit' => 'Cuti Sakit',
                        'Cuti Melahirkan' => 'Cuti Melahirkan',
                        'Cuti Alasan Penting' => 'Cuti Alasan Penting',
                        'Cuti Luar Tanggungan' => 'Cuti di Luar Tanggungan Negara',
                        'Cuti Tugas' => 'Cuti Tugas / Dinas Luar',
                        'Cuti Belajar' => 'Cuti / Tugas Belajar',
                        'Izin Lainnya' => 'Izin / Keterangan Lainnya',
                    ]),

                Tables\Filters\SelectFilter::make('status_global')
                    ->label('Status')
                    ->options([
                        Cuti::STATUS_MENUNGGU_ATASAN => Cuti::STATUS_MENUNGGU_ATASAN,
                        Cuti::STATUS_MENUNGGU_TU     => Cuti::STATUS_MENUNGGU_TU,
                        Cuti::STATUS_MENUNGGU_KEPALA => Cuti::STATUS_MENUNGGU_KEPALA,
                        Cuti::STATUS_DISETUJUI       => Cuti::STATUS_DISETUJUI,
                        Cuti::STATUS_DITOLAK         => Cuti::STATUS_DITOLAK,
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        // Penolakan disimpan sebagai "Ditolak (oleh <penyetuju>)",
                        // jadi cukup prefix match, bukan kesamaan persis.
                        if ($data['value'] === Cuti::STATUS_DITOLAK) {
                            return $query->where('status_global', 'like', Cuti::STATUS_DITOLAK . '%');
                        }
                        return $query->where('status_global', $data['value']);
                    }),

                Tables\Filters\SelectFilter::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->relationship('employee.unitKerja', 'nama')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => in_array(Auth::user()->role, ['admin', 'tata_usaha', 'kepala_stasiun'], true)),

                Tables\Filters\Filter::make('periode')
                    ->form([
                        TextInput::make('periode')
                            ->label('Periode (YYYY-MM)')
                            ->placeholder('Contoh: ' . now()->format('Y-m')),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['periode'] ?? null, fn ($q, $p) => $q->where('periode', $p))
                    ),
            ])
            ->actions([
            // Lihat Detail Pengajuan — pakai Action biasa (bukan ViewAction)
            // karena ViewAction mencoba mount form ($data) yang tidak ada di ListCutis
            Tables\Actions\Action::make('lihat_detail')
                ->label('Lihat Detail')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading(fn ($record) => 'Detail Pengajuan Cuti - ' . ($record->employee?->nama ?? '-'))
                ->modalWidth('5xl')
                ->modalContent(fn ($record) => view('filament.resources.cuti.view-detail', ['record' => $record]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            // ✅ Tombol Cetak Form PDF langsung di tabel (tidak masuk dropdown titik 3)
            Action::make('exportFormPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->action(function (Cuti $record) {
                    return new StreamedResponse(function () use ($record) {
                        app(FormCutiGenerator::class)->generate($record);
                    }, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="formulir_cuti.pdf"',
                    ]);
                }),

            // PERBAIKAN: Admin bisa edit semua yang pending, non-admin hanya tahap 1 dan hanya pemilik
            Tables\Actions\EditAction::make()
                ->visible(fn (Cuti $record) =>
                    Auth::user()->role === 'admin'
                        ? in_array($record->status_global, Cuti::STATUS_PENDING, true)
                        : (
                            $record->status_global === Cuti::STATUS_MENUNGGU_ATASAN &&
                            Auth::id() === $record->employee?->user_id
                        )
                ),

            // PERBAIKAN: Hapus - admin bisa hapus semua, non-admin hanya tahap 1.
            // Jika hapus cuti Tahunan yang Disetujui, kembalikan kuota.
            Tables\Actions\DeleteAction::make()
                ->visible(fn (Cuti $record) =>
                    Auth::user()->role === 'admin' ||
                    $record->status_global === Cuti::STATUS_MENUNGGU_ATASAN
                )
                ->action(function (Cuti $record) {
                    $employee = $record->employee;
                    $isApprovedAnnual = $record->status_global === Cuti::STATUS_DISETUJUI && $record->jenis_cuti === 'Cuti Tahunan';
                    $durasi = (int) $record->lama_cuti;

                    if ($isApprovedAnnual && $employee) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $durasi) {
                            $locked = \App\Models\Employee::whereKey($employee->id)->lockForUpdate()->first();
                            $locked->sisa_cuti_tahunan += $durasi;
                            $locked->save();
                        });
                    }
                    $record->delete();
                }),

            // === AKSI APPROVAL ALUR BERTINGKAT (KETUA UNIT -> TU -> KEPALA) ===
            Tables\Actions\ActionGroup::make([
                // === AKSI TAHAP 1: KETUA TIM / ATASAN LANGSUNG ===
                Action::make('Proses Atasan Langsung')
                        ->label('Proses (Ketua Unit)')
                        ->icon('heroicon-o-pencil')
                        ->color('info')
                        ->visible(fn (Cuti $record) => self::canProsesSebagaiKetua($record))
                        ->form([
                            Select::make('status_atasan_langsung')
                                ->label('Persetujuan Ketua Unit')
                                ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                                ->required(),
                            Textarea::make('tanggapan_atasan_langsung')
                                ->label('Catatan / Tanggapan'),
                        ])
                        ->action(function (Cuti $record, array $data): void {
                            // Guard otorisasi (bukan hanya menyembunyikan tombol).
                            if (! self::canProsesSebagaiKetua($record)) {
                                Notification::make()
                                    ->title('Tidak Diizinkan')
                                    ->body('Anda tidak memiliki wewenang memproses pengajuan ini.')
                                    ->danger()->send();
                                return;
                            }

                            $record->status_atasan_langsung = $data['status_atasan_langsung'];
                            $record->tanggapan_atasan_langsung = $data['tanggapan_atasan_langsung'];
                            $record->atasan_langsung_approver_id = Auth::id();

                            if ($data['status_atasan_langsung'] === 'approved') {
                                $record->status_global = Cuti::STATUS_MENUNGGU_TU;
                                
                                // === NOTIFIKASI KE TATA USAHA ===
                                $penerima = \App\Models\User::where('role', 'tata_usaha')->get();
                                
                                Notification::make()
                                    ->title('Persetujuan Cuti (Tahap 2)')
                                    ->body('Pengajuan ' . ($record->employee?->nama ?? '-') . ' telah disetujui Ketua Tim. Menunggu verifikasi TU.')
                                    ->success()
                                    ->sendToDatabase($penerima);

                            } else {
                                $namaUnit = $record->employee?->unitKerja?->nama ?? 'Unit';
                                $record->status_global = Cuti::statusDitolak("Ketua $namaUnit");

                                if ($record->employee?->user) {
                                    Notification::make()
                                        ->title('Pengajuan Ditolak')
                                        ->body("Mohon maaf, pengajuan cuti Anda ditolak oleh Ketua Tim.")
                                        ->danger()
                                        ->sendToDatabase($record->employee->user);
                                }
                            }
                            $record->save();
                        }),

                    // === AKSI TAHAP 2: KASUBBAG TATA USAHA ===
                    Action::make('Proses Tata Usaha')
                        ->label('Proses (Bag. SDM/TU)')
                        ->icon('heroicon-o-pencil')
                        ->color('info')
                        ->visible(fn (Cuti $record) => self::canProsesSebagaiTu($record))
                        ->form([
                            Select::make('status_tata_usaha')
                                ->label('Persetujuan Kasubbag Tata Usaha')
                                ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                                ->required(),
                            Textarea::make('tanggapan_tata_usaha')
                                ->label('Catatan / Tanggapan'),
                        ])
                        ->action(function (Cuti $record, array $data): void {
                            if (! self::canProsesSebagaiTu($record)) {
                                Notification::make()
                                    ->title('Tidak Diizinkan')
                                    ->body('Anda tidak memiliki wewenang memproses pengajuan ini.')
                                    ->danger()->send();
                                return;
                            }

                            $record->status_tata_usaha = $data['status_tata_usaha'];
                            $record->tanggapan_tata_usaha = $data['tanggapan_tata_usaha'];
                            $record->tata_usaha_approver_id = Auth::id();

                            if ($data['status_tata_usaha'] === 'approved') {
                                $record->status_global = Cuti::STATUS_MENUNGGU_KEPALA;

                                $penerima = \App\Models\User::where('role', 'kepala_stasiun')->get();

                                Notification::make()
                                    ->title('Persetujuan Cuti (Final)')
                                    ->body('Pengajuan ' . ($record->employee?->nama ?? '-') . ' menunggu persetujuan akhir Anda.')
                                    ->info()
                                    ->sendToDatabase($penerima);

                            } else {
                                $record->status_global = Cuti::statusDitolak('Tata Usaha');

                                if ($record->employee?->user) {
                                    Notification::make()
                                        ->title('Pengajuan Ditolak')
                                        ->body("Pengajuan cuti ditolak oleh Tata Usaha. Cek aplikasi untuk detail.")
                                        ->danger()
                                        ->sendToDatabase($record->employee->user);
                                }
                            }
                            $record->save();
                        }),

                    // === AKSI TAHAP 3: KEPALA STASIUN ===
                    Action::make('Proses Kepala Stasiun')
                        ->label('Proses (Kepala)')
                        ->icon('heroicon-o-pencil')
                        ->color('info')
                        ->visible(fn (Cuti $record) => self::canProsesSebagaiKepala($record))
                        ->form([
                            Select::make('status_kepala')
                                ->label('Persetujuan Kepala Stasiun')
                                ->options(['approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                                ->required(),
                            Textarea::make('tanggapan_kepala')
                                ->label('Catatan / Tanggapan'),
                        ])
                        ->action(function (Cuti $record, array $data): void {
                            if (! self::canProsesSebagaiKepala($record)) {
                                Notification::make()
                                    ->title('Tidak Diizinkan')
                                    ->body('Anda tidak memiliki wewenang memproses pengajuan ini.')
                                    ->danger()->send();
                                return;
                            }

                            $employee = $record->employee;
                            if (! $employee) {
                                Notification::make()
                                    ->title('Data Pegawai Hilang')
                                    ->body('Approval dibatalkan karena data pegawai tidak ditemukan.')
                                    ->danger()->send();
                                return;
                            }

                            if ($data['status_kepala'] === 'approved') {
                                try {
                                    \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data, $employee) {
                                        // Kunci baris employee agar race 2 approval tidak overspend
                                        $locked = \App\Models\Employee::whereKey($employee->id)->lockForUpdate()->first();
                                        $durasiCuti = (int) $record->lama_cuti;

                                        if ($record->jenis_cuti === 'Cuti Tahunan') {
                                            if (($locked->sisa_cuti_tahunan ?? 0) < $durasiCuti) {
                                                throw new \RuntimeException(
                                                    "Sisa cuti {$locked->sisa_cuti_tahunan} hari tidak cukup untuk {$durasiCuti} hari."
                                                );
                                            }
                                            $locked->sisa_cuti_tahunan -= $durasiCuti;
                                            $locked->save();
                                        }

                                        $record->status_kepala = 'approved';
                                        $record->tanggapan_kepala = $data['tanggapan_kepala'] ?? null;
                                        $record->kepala_stasiun_approver_id = Auth::id();
                                        $record->status_global = Cuti::STATUS_DISETUJUI;
                                        $record->save();
                                    });
                                } catch (\RuntimeException $e) {
                                    // Saldo kurang: JANGAN setujui, tetap Menunggu Kepala + beri tahu approver
                                    $record->status_kepala = 'pending';
                                    $record->tanggapan_kepala = $data['tanggapan_kepala'] ?? null;
                                    $record->kepala_stasiun_approver_id = Auth::id();
                                    $record->save();

                                    Notification::make()
                                        ->title('Gagal Menyetujui: Sisa Cuti Kurang')
                                        ->body($e->getMessage() . ' Pengajuan tetap Menunggu Persetujuan Kepala.')
                                        ->danger()->send();
                                    return;
                                }

                                // === NOTIFIKASI SUKSES KE PEGAWAI ===
                                if ($employee->user) {
                                    Notification::make()
                                        ->title('Cuti DISETUJUI! 🎉')
                                        ->body("Selamat, pengajuan cuti Anda selama {$record->lama_cuti} hari telah disetujui Kepala Stasiun. Silakan cetak formulir.")
                                        ->success()
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('Cetak PDF')
                                                ->url(route('filament.admin.resources.cutis.index'))
                                        ])
                                        ->sendToDatabase($employee->user);
                                }

                            } else {
                                $record->status_kepala = 'rejected';
                                $record->tanggapan_kepala = $data['tanggapan_kepala'] ?? null;
                                $record->kepala_stasiun_approver_id = Auth::id();
                                $record->status_global = Cuti::statusDitolak('Kepala Stasiun');

                                // === NOTIFIKASI DITOLAK KE PEGAWAI ===
                                if ($employee->user) {
                                    Notification::make()
                                        ->title('Pengajuan Ditolak')
                                        ->body("Mohon maaf, pengajuan cuti ditolak oleh Kepala Stasiun.")
                                        ->danger()
                                        ->sendToDatabase($employee->user);
                                }
                                $record->save();
                            }
                        }),
                ])->label('Proses Persetujuan')->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()->role === 'admin') // Hanya admin bisa bulk delete
                        ->action(function (Collection $records) {
                            $cutis = $records->load('employee');
                            \Illuminate\Support\Facades\DB::transaction(function () use ($cutis) {
                                foreach ($cutis as $cuti) {
                                    $employee = $cuti->employee;
                                    $isApprovedAnnual = $cuti->status_global === Cuti::STATUS_DISETUJUI && $cuti->jenis_cuti === 'Cuti Tahunan';
                                    $durasi = (int) $cuti->lama_cuti;

                                    // Jika cuti Tahunan yang sudah disetujui, kembalikan kuota
                                    if ($isApprovedAnnual && $employee) {
                                        $locked = \App\Models\Employee::whereKey($employee->id)->lockForUpdate()->first();
                                        $locked->sisa_cuti_tahunan += $durasi;
                                        $locked->save();
                                    }

                                    // Hapus record
                                    $cuti->delete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with(['employee.user', 'employee.unitKerja']);

        switch ($user->role) {
            case 'pegawai':
                if (!$user->employee) { return $query->where('id', 0); }
                return $query->where('employee_id', $user->employee->id);
            
            case 'ketua_tim': 
                $unitKerja = UnitKerja::where('ketua_user_id', $user->id)->first();
                
                if ($unitKerja) {
                    return $query->whereHas('employee', function ($q) use ($unitKerja) {
                        $q->where('unit_kerja_id', $unitKerja->id);
                    })->where('status_global', Cuti::STATUS_MENUNGGU_ATASAN);
                }
                return $query->where('id', 0); 

            case 'tata_usaha':
                return $query->where('status_global', Cuti::STATUS_MENUNGGU_TU);
            
            case 'kepala_stasiun':
                return $query->where('status_global', Cuti::STATUS_MENUNGGU_KEPALA);

            case 'admin':
                return $query;
            
            default:
                return $query->where('id', 0);
        }
    }

    // ==========================================================
    // Helper otorisasi approval.
    // Dipakai di visible() (menyembunyikan tombol) DAN di dalam
    // ->action() (validasi server-side), karena visible() Filament
    // hanya menyembunyikan UI — komponen Livewire bisa dipanggil
    // langsung dari klien.
    // ==========================================================

    public static function canProsesSebagaiKetua(Cuti $record): bool
    {
        return $record->status_global === Cuti::STATUS_MENUNGGU_ATASAN
            && $record->employee?->unitKerja !== null
            && $record->employee->unitKerja->ketua_user_id === Auth::id()
            // Pencegahan self-approval: pengajunya sendiri tidak bisa disetujui sendiri.
            && Auth::id() !== $record->employee?->user_id;
    }

    public static function canProsesSebagaiTu(Cuti $record): bool
    {
        return Auth::user()?->role === 'tata_usaha'
            && $record->status_global === Cuti::STATUS_MENUNGGU_TU;
    }

    public static function canProsesSebagaiKepala(Cuti $record): bool
    {
        return Auth::user()?->role === 'kepala_stasiun'
            && $record->status_global === Cuti::STATUS_MENUNGGU_KEPALA;
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