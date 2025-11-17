{{-- Custom Footer for Filament Admin --}}
<div class="mt-auto border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 py-4 px-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600 dark:text-gray-400">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>© {{ date('Y') }} <strong class="font-semibold text-gray-900 dark:text-white">TVRI Yogyakarta</strong></span>
        </div>
        
        <div class="flex items-center gap-4">
            <span class="hidden sm:inline">Sistem E-Cuti v1.0</span>
            <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-1 rounded-full">
                Online
            </span>
        </div>
    </div>
</div>

{{-- Custom Styles for Enhanced UI --}}
<style>
    /* Smooth scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgb(243 244 246);
    }
    
    .dark ::-webkit-scrollbar-track {
        background: rgb(17 24 39);
    }
    
    ::-webkit-scrollbar-thumb {
        background: rgb(209 213 219);
        border-radius: 4px;
    }
    
    .dark ::-webkit-scrollbar-thumb {
        background: rgb(55 65 81);
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: rgb(156 163 175);
    }
    
    .dark ::-webkit-scrollbar-thumb:hover {
        background: rgb(75 85 99);
    }
    
    /* Enhanced focus states */
    .fi-input:focus,
    .fi-textarea:focus,
    .fi-select:focus {
        border-color: rgb(59 130 246) !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    /* Smooth transitions */
    .fi-sidebar-nav-item,
    .fi-dropdown-list-item {
        transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Card hover effect */
    .fi-section {
        transition: box-shadow 200ms ease;
    }
    
    .fi-section:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
</style>