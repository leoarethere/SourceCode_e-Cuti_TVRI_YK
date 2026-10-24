@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    
    // State management
    $statePath = $getStatePath();
    $userRole = Auth::user()?->role;
    $canBackdate = in_array($userRole, ['admin', 'tata_usaha', 'kepala_stasiun']);
    
    // Handle data existing saat edit
    $selectedDates = [];
    $rawState = $getState();
    
    if (is_array($rawState)) {
        $selectedDates = $rawState;
    } elseif (is_string($rawState)) {
        $decoded = json_decode($rawState, true);
        $selectedDates = is_array($decoded) ? $decoded : [];
    }
    
    // Get current month/year dari Alpine state (bukan query string lagi)
    $currentMonth = now()->month;
    $currentYear = now()->year;
    
    // Generate calendar
    $firstDay = Carbon::create($currentYear, $currentMonth, 1);
    $lastDay = $firstDay->copy()->endOfMonth();
    $daysInMonth = $lastDay->day;
    $startDayOfWeek = $firstDay->dayOfWeek;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
        selectedDates: @js($selectedDates),
        currentMonth: {{ $currentMonth }},
        currentYear: {{ $currentYear }},
        daysInMonth: 31,
        startDayOfWeek: 0,
        monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        
        // ✅ FIX 1: Debounced sync untuk mengurangi lag (timeout terpisah agar tidak saling batal)
        syncTimeout: null,
        rangeTimeout: null,
        
        init() {
            this.calculateCalendar();
            this.updateRangeDisplay();
        },
        
        calculateCalendar() {
            const firstDay = new Date(this.currentYear, this.currentMonth - 1, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth, 0);
            this.daysInMonth = lastDay.getDate();
            this.startDayOfWeek = firstDay.getDay();
        },
        
        toggleDate(date) {
            const index = this.selectedDates.indexOf(date);
            if (index > -1) {
                this.selectedDates.splice(index, 1);
            } else {
                this.selectedDates.push(date);
            }
            this.selectedDates.sort();
            
            // ✅ FIX: Debounce sync - tunggu 300ms sebelum sync ke server
            clearTimeout(this.syncTimeout);
            this.syncTimeout = setTimeout(() => {
                $wire.set('{{ $statePath }}', this.selectedDates);
                this.updateRangeDisplay();
            }, 300);
        },
        
        isSelected(date) {
            return this.selectedDates.includes(date);
        },
        
        // ✅ FIX 2: Ganti bulan tanpa reload halaman
        changeMonth(delta) {
            this.currentMonth += delta;
            
            if (this.currentMonth > 12) {
                this.currentMonth = 1;
                this.currentYear++;
            } else if (this.currentMonth < 1) {
                this.currentMonth = 12;
                this.currentYear--;
            }
            
            this.calculateCalendar();
        },
        
        // ✅ FIX 3: Helper untuk cek tanggal masa lalu
        isPastDate(day) {
            const checkDate = new Date(this.currentYear, this.currentMonth - 1, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return checkDate < today;
        },
        
        // ✅ FIX 4: Helper untuk cek weekend
        isWeekend(day) {
            const checkDate = new Date(this.currentYear, this.currentMonth - 1, day);
            const dayOfWeek = checkDate.getDay();
            return dayOfWeek === 0 || dayOfWeek === 6;
        },
        
        getDateString(day) {
            const month = String(this.currentMonth).padStart(2, '0');
            const dayStr = String(day).padStart(2, '0');
            return `${this.currentYear}-${month}-${dayStr}`;
        },
        
        updateRangeDisplay() {
            if (this.selectedDates.length > 0) {
                const sorted = [...this.selectedDates].sort();
                const first = sorted[0];
                const last = sorted[sorted.length - 1];
                
                // Batch update untuk hidden fields (timeout sendiri, jangan batalkan sync tanggal)
                clearTimeout(this.rangeTimeout);
                this.rangeTimeout = setTimeout(() => {
                    $wire.set('data.tanggal_mulai', first);
                    $wire.set('data.tanggal_akhir', last);
                }, 500);
            }
        },
        
        // ✅ BONUS: Quick select shortcuts (merge, jangan hapus bulan lain)
        selectWeekdays() {
            const merged = new Set(this.selectedDates);
            for (let day = 1; day <= this.daysInMonth; day++) {
                if (!this.isWeekend(day)) {
                    merged.add(this.getDateString(day));
                }
            }
            this.selectedDates = [...merged].sort();
            $wire.set('{{ $statePath }}', this.selectedDates);
            this.updateRangeDisplay();
        },
        
        clearSelection() {
            this.selectedDates = [];
            $wire.set('{{ $statePath }}', []);
            this.updateRangeDisplay();
        }
    }" class="space-y-4">
        
        <!-- Header Calendar dengan Quick Actions -->
        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <button 
                type="button"
                @click="changeMonth(-1)"
                class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
                ← Bulan Lalu
            </button>
            
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="monthNames[currentMonth - 1] + ' ' + currentYear"></h3>
                <div class="flex gap-2 mt-2">
                    <button 
                        type="button"
                        @click="selectWeekdays()"
                        class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800"
                        title="Pilih semua hari kerja (Sen-Jum) di bulan ini"
                    >
                        Pilih Hari Kerja
                    </button>
                    <button 
                        type="button"
                        @click="clearSelection()"
                        class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800"
                    >
                        Reset
                    </button>
                </div>
            </div>
            
            <button 
                type="button"
                @click="changeMonth(1)"
                class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
                Bulan Depan →
            </button>
        </div>

        <!-- Day Names -->
        <div class="grid grid-cols-7 gap-1 text-center font-medium text-sm text-gray-600 dark:text-gray-400">
            <div class="p-2 text-red-600 dark:text-red-400">Min</div>
            <div class="p-2">Sen</div>
            <div class="p-2">Sel</div>
            <div class="p-2">Rab</div>
            <div class="p-2">Kam</div>
            <div class="p-2">Jum</div>
            <div class="p-2 text-red-600 dark:text-red-400">Sab</div>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <!-- Empty cells before first day -->
            <template x-for="i in startDayOfWeek" :key="'empty-' + i">
                <div class="p-2"></div>
            </template>
            
            <!-- Days -->
            <template x-for="day in daysInMonth" :key="day">
                <div>
                    <label 
                        class="flex items-center justify-center p-2 cursor-pointer rounded-lg border-2 transition-all"
                        :class="{
                            'bg-red-50 dark:bg-red-900/20': isWeekend(day),
                            'bg-white dark:bg-gray-800': !isWeekend(day),
                            'opacity-40': isPastDate(day),
                            'hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20': !isPastDate(day),
                            'border-primary-600 bg-primary-100 dark:bg-primary-900/40 font-bold': isSelected(getDateString(day)),
                            'border-gray-200 dark:border-gray-700': !isSelected(getDateString(day))
                        }"
                    >
                        <input 
                            type="checkbox"
                            class="sr-only"
                            :value="getDateString(day)"
                            @click="toggleDate(getDateString(day))"
                            :checked="isSelected(getDateString(day))"
                        >
                        <span 
                            class="text-sm"
                            :class="{
                                'text-red-600 dark:text-red-400': isWeekend(day),
                                'text-gray-900 dark:text-gray-100': !isWeekend(day),
                                'font-bold text-primary-700 dark:text-primary-300': isSelected(getDateString(day))
                            }"
                            x-text="day"
                        ></span>
                    </label>
                </div>
            </template>
        </div>

        <!-- Summary dengan Info Lebih Detail -->
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    Tanggal Dipilih:
                </span>
                <span class="text-lg font-bold text-blue-700 dark:text-blue-300" x-text="selectedDates.length + ' Hari'">
                    0 Hari
                </span>
            </div>
            
            <div class="mt-2 text-xs text-blue-700 dark:text-blue-300" x-show="selectedDates.length > 0">
                <span x-text="selectedDates.length > 3 
                    ? selectedDates[0] + ' ... ' + selectedDates[selectedDates.length - 1] 
                    : selectedDates.join(', ')">
                </span>
            </div>
            
            <!-- Warning untuk backdate -->
            <div x-show="selectedDates.some(date => new Date(date) < new Date().setHours(0,0,0,0))" 
                 class="mt-2 p-2 rounded text-xs {{ $canBackdate ? 'bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200' : 'bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200' }}">
                @if($canBackdate)
                    ⚠️ Anda memilih tanggal lampau (backdate). Pastikan sudah ada persetujuan yang sesuai.
                @else
                    🚫 <strong>Peringatan:</strong> Pengajuan cuti tanggal lampau (backdate) tidak diizinkan untuk Pegawai / Ketua Tim. Silakan hubungi Admin atau Tata Usaha.
                @endif
            </div>
        </div>

        <!-- Info & Legends -->
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <p>✅ <strong>Klik tanggal</strong> untuk memilih/membatalkan cuti</p>
            <p>📅 Anda bisa memilih tanggal tidak berurutan</p>
            @if($canBackdate)
                <p>⏮️ <strong>Backdate diizinkan</strong> untuk peran Anda (Admin / TU / Kepala)</p>
            @else
                <p>⏮️ <strong>Tanggal masa lalu:</strong> Hanya dapat diajukan melalui Admin atau Tata Usaha</p>
            @endif
        </div>
    </div>
</x-dynamic-component>