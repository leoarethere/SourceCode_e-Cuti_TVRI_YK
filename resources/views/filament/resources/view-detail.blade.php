@php
    use Carbon\Carbon;
    
    // Helper untuk status badge
    $statusColor = match(true) {
        str_contains($record->status_global, 'Disetujui') => 'success',
        str_contains($record->status_global, 'Ditolak') => 'danger',
        default => 'warning'
    };
    
    // --- PERBAIKAN MASA KERJA DISINI ---
    $masaKerja = '-';
    if ($record->employee->tanggal_bergabung) {
        $joinDate = Carbon::parse($record->employee->tanggal_bergabung);
        $diff = $joinDate->diff(now()); // Mengembalikan objek DateInterval
        
        $years = $diff->y; // Pasti Integer (Bulat)
        $months = $diff->m; // Pasti Integer (Bulat)
        
        if ($years > 0) {
            $masaKerja = $years . ' Tahun' . ($months > 0 ? ' ' . $months . ' Bulan' : '');
        } else {
            $masaKerja = $months . ' Bulan';
        }
    }
    // -----------------------------------
    
    // Format tanggal cuti
    $tanggalCutiDisplay = '-';
    if (!empty($record->tanggal_cuti_array) && is_array($record->tanggal_cuti_array)) {
        $dates = collect($record->tanggal_cuti_array)->sort()->values();
        
        if ($dates->count() <= 10) {
            $tanggalCutiDisplay = $dates->map(fn($d) => Carbon::parse($d)->isoFormat('D MMMM Y'))->join(', ');
        } else {
            // Grup per bulan
            $grouped = $dates->groupBy(fn($d) => Carbon::parse($d)->format('Y-m'));
            $tanggalCutiDisplay = $grouped->map(function($monthDates, $month) {
                $monthName = Carbon::parse($monthDates->first())->isoFormat('MMMM Y');
                $days = $monthDates->map(fn($d) => Carbon::parse($d)->day)->join(', ');
                return "Tanggal $days $monthName";
            })->join('<br>');
        }
    } elseif ($record->tanggal_mulai && $record->tanggal_akhir) {
        $tanggalCutiDisplay = Carbon::parse($record->tanggal_mulai)->isoFormat('D MMMM Y') . ' s/d ' . Carbon::parse($record->tanggal_akhir)->isoFormat('D MMMM Y');
    }
@endphp

<div class="space-y-6">
    
    {{-- Status Badge Header --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Status Pengajuan
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Diajukan pada {{ Carbon::parse($record->created_at)->isoFormat('dddd, D MMMM Y [pukul] HH:mm') }} WIB
            </p>
        </div>
        <x-filament::badge :color="$statusColor" size="lg">
            {{ $record->status_global }}
        </x-filament::badge>
    </div>

    {{-- Grid 2 Kolom --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- KOLOM KIRI: Data Pegawai --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Data Pegawai
                </h4>
                
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Nama Lengkap:</dt>
                        <dd class="text-gray-900 dark:text-white font-semibold">{{ $record->employee->nama }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">NIP:</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $record->employee->NIP }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Jabatan:</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $record->employee->jabatan }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Unit Kerja:</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $record->employee->unitKerja->nama ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Masa Kerja:</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $masaKerja }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Nomor Telepon:</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $record->employee->telp ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                        <dt class="font-medium text-primary-600 dark:text-primary-400">Sisa Cuti Tahunan:</dt>
                        <dd class="text-primary-600 dark:text-primary-400 font-bold text-lg">
                            {{ $record->employee->sisa_cuti_tahunan }} Hari
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- KOLOM KANAN: Data Cuti --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Data Pengajuan Cuti
                </h4>
                
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Jenis Cuti:</dt>
                        <dd class="text-gray-900 dark:text-white font-semibold">{{ $record->jenis_cuti }}</dd>
                    </div>
                    
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="font-medium text-gray-600 dark:text-gray-400 mb-2">Detail Tanggal Cuti:</dt>
                        <dd class="text-gray-900 dark:text-white text-xs leading-relaxed bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            {!! $tanggalCutiDisplay !!}
                        </dd>
                    </div>
                    
                    <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="font-medium text-gray-600 dark:text-gray-400">Total Lama Cuti:</dt>
                        <dd class="text-primary-600 dark:text-primary-400 font-bold text-lg">
                            {{ $record->lama_cuti }} Hari
                        </dd>
                    </div>
                    
                    @if($record->tanggal_mulai && $record->tanggal_akhir)
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="font-medium text-gray-600 dark:text-gray-400 mb-1">Range Periode:</dt>
                        <dd class="text-gray-700 dark:text-gray-300 text-xs">
                            {{ Carbon::parse($record->tanggal_mulai)->isoFormat('D MMMM Y') }} 
                            s/d 
                            {{ Carbon::parse($record->tanggal_akhir)->isoFormat('D MMMM Y') }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Alasan & Alamat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Alasan Cuti</h4>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ $record->alasan }}
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Alamat Selama Cuti</h4>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ $record->alamat_selama_cuti }}
            </p>
        </div>
    </div>

    {{-- Lampiran (jika ada) --}}
    @if($record->lampiran_link)
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <div class="flex-1">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Lampiran Dokumen:</span>
                <a href="{{ $record->lampiran_link }}" target="_blank" 
                   class="ml-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    Lihat Dokumen →
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Timeline Approval --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Timeline Persetujuan
        </h4>

        <ol class="relative border-l border-gray-300 dark:border-gray-600 ml-3 space-y-6">
            
            {{-- Step 1: Ketua Unit --}}
            <li class="ml-6">
                <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white dark:ring-gray-900
                    {{ $record->status_atasan_langsung === 'approved' ? 'bg-green-200 dark:bg-green-900' : ($record->status_atasan_langsung === 'rejected' ? 'bg-red-200 dark:bg-red-900' : 'bg-gray-200 dark:bg-gray-700') }}">
                    @if($record->status_atasan_langsung === 'approved')
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @elseif($record->status_atasan_langsung === 'rejected')
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    @endif
                </span>
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    Ketua {{ $record->employee->unitKerja->nama ?? 'Unit' }}
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                    Status: 
                    <span class="font-semibold {{ $record->status_atasan_langsung === 'approved' ? 'text-green-600' : ($record->status_atasan_langsung === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ strtoupper($record->status_atasan_langsung) }}
                    </span>
                </p>
                @if($record->tanggapan_atasan_langsung)
                <p class="text-xs text-gray-700 dark:text-gray-300 italic bg-gray-50 dark:bg-gray-800 p-2 rounded">
                    "{{ $record->tanggapan_atasan_langsung }}"
                </p>
                @endif
                @if($record->atasanLangsungApprover)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Diproses oleh: {{ $record->atasanLangsungApprover->name }}
                </p>
                @endif
            </li>

            {{-- Step 2: Tata Usaha --}}
            <li class="ml-6">
                <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white dark:ring-gray-900
                    {{ $record->status_tata_usaha === 'approved' ? 'bg-green-200 dark:bg-green-900' : ($record->status_tata_usaha === 'rejected' ? 'bg-red-200 dark:bg-red-900' : 'bg-gray-200 dark:bg-gray-700') }}">
                    @if($record->status_tata_usaha === 'approved')
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @elseif($record->status_tata_usaha === 'rejected')
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    @endif
                </span>
                <h3 class="font-semibold text-gray-900 dark:text-white">Kasubbag Tata Usaha</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                    Status: 
                    <span class="font-semibold {{ $record->status_tata_usaha === 'approved' ? 'text-green-600' : ($record->status_tata_usaha === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ strtoupper($record->status_tata_usaha) }}
                    </span>
                </p>
                @if($record->tanggapan_tata_usaha)
                <p class="text-xs text-gray-700 dark:text-gray-300 italic bg-gray-50 dark:bg-gray-800 p-2 rounded">
                    "{{ $record->tanggapan_tata_usaha }}"
                </p>
                @endif
                @if($record->tataUsahaApprover)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Diproses oleh: {{ $record->tataUsahaApprover->name }}
                </p>
                @endif
            </li>

            {{-- Step 3: Kepala Stasiun --}}
            <li class="ml-6">
                <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white dark:ring-gray-900
                    {{ $record->status_kepala === 'approved' ? 'bg-green-200 dark:bg-green-900' : ($record->status_kepala === 'rejected' ? 'bg-red-200 dark:bg-red-900' : 'bg-gray-200 dark:bg-gray-700') }}">
                    @if($record->status_kepala === 'approved')
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @elseif($record->status_kepala === 'rejected')
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    @endif
                </span>
                <h3 class="font-semibold text-gray-900 dark:text-white">Kepala Stasiun</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                    Status: 
                    <span class="font-semibold {{ $record->status_kepala === 'approved' ? 'text-green-600' : ($record->status_kepala === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ strtoupper($record->status_kepala) }}
                    </span>
                </p>
                @if($record->tanggapan_kepala)
                <p class="text-xs text-gray-700 dark:text-gray-300 italic bg-gray-50 dark:bg-gray-800 p-2 rounded">
                    "{{ $record->tanggapan_kepala }}"
                </p>
                @endif
                @if($record->kepalaApprover)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Diproses oleh: {{ $record->kepalaApprover->name }}
                </p>
                @endif
            </li>
        </ol>
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        @if($record->status_global === 'Disetujui')
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Tutup detail ini lalu klik <span class="font-semibold">Cetak Form PDF</span> pada baris tabel untuk mengunduh formulir.
        </p>
        @endif
    </div>

</div>