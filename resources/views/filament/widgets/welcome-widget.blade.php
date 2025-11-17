<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <div class="flex-shrink-0">
                <x-filament-panels::avatar.user size="lg" :user="auth()->user()" />
            </div>

            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Selamat Datang, {{ auth()->user()->name }}! 👋
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Anda login sebagai <strong>{{ ucfirst(auth()->user()->role) }}</strong>.
                    
                    @if(auth()->user()->role !== 'pegawai')
                        <br>
                        <span class="text-primary-600">
                            Ada pengajuan cuti yang menunggu persetujuan Anda hari ini.
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>