@props(['entry'])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                KPI Performance Data
            </h3>
            <span class="px-3 py-1 bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-full text-xs font-medium">Required Fields *</span>
        </div>
    </div>
    
    <div class="p-6">
        <!-- Date and Achievement Row -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Entry Date <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="date" name="entry_date" required
                           value="{{ old('entry_date', $entry->entry_date->format('Y-m-d')) }}"
                           class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    <svg class="w-5 h-5 absolute right-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Achievement Status
                </label>
                @php
                    $achievement = $entry->target > 0 ? round(($entry->actual / $entry->target) * 100, 1) : 0;
                    $status = $achievement >= 100 ? 'success' : ($achievement >= 80 ? 'warning' : 'danger');
                @endphp
                <div class="h-11 flex items-center">
                    <div class="flex items-center space-x-3 w-full">
                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-300 {{ $status === 'success' ? 'bg-green-500' : ($status === 'warning' ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                 style="width: {{ min($achievement, 100) }}%"></div>
                        </div>
                        <span class="text-lg font-bold {{ $status === 'success' ? 'text-green-700 dark:text-green-300' : ($status === 'warning' ? 'text-yellow-700 dark:text-yellow-300' : 'text-red-700 dark:text-red-300') }}">
                            {{ $achievement }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target & Actual Values -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Target Value <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.01" name="target" required
                           value="{{ old('target', $entry->target) }}"
                           class="w-full px-4 py-3 text-lg font-semibold border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    <span class="absolute right-3 top-3 text-sm text-gray-500 dark:text-gray-400">unit</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Actual Value <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.01" name="actual" required
                           value="{{ old('actual', $entry->actual) }}"
                           class="w-full px-4 py-3 text-lg font-semibold border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    <span class="absolute right-3 top-3 text-sm text-gray-500 dark:text-gray-400">unit</span>
                </div>
            </div>
        </div>

        <!-- Gap Analysis -->
        @php $gap = $entry->target - $entry->actual; @endphp
        <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Performance Gap:</span>
                <span class="text-lg font-bold {{ $gap > 0 ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }}">
                    {{ $gap > 0 ? '-' : '+' }}{{ number_format(abs($gap), 2) }}
                </span>
            </div>
        </div>
    </div>
</div>
