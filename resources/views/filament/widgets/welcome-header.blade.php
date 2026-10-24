<x-filament-widgets::widget>
    <div class="relative p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-700 overflow-hidden">
        
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-blue-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white tracking-tight">
                        {{ $greeting }}, {{ $user->name }}! 👋
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Anda login sebagai <span class="font-semibold text-blue-600">{{ $role }}</span>.
                        Selamat bekerja dan beraktivitas.
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-end text-right bg-gray-50 dark:bg-gray-800 px-4 py-2 rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold text-gray-800 dark:text-white">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>