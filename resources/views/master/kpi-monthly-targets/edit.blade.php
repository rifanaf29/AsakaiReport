<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-3xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('master.kpi-monthly-targets.index', ['year' => $kpiMonthlyTarget->target_year]) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Edit Monthly Target</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                {{ $kpiMonthlyTarget->template->name }} - {{ $kpiMonthlyTarget->month_name }}
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.kpi-monthly-targets.update', $kpiMonthlyTarget) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <!-- Template Info -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Template:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $kpiMonthlyTarget->template->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Period:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $kpiMonthlyTarget->month_name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Department:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $kpiMonthlyTarget->template->department->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Unit:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $kpiMonthlyTarget->template->target_unit }}</span>
                        </div>
                    </div>
                </div>

                <!-- Target Value -->
                <div class="mb-6">
                    <label for="target_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Target Value <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            id="target_value" 
                            name="target_value" 
                            value="{{ old('target_value', $kpiMonthlyTarget->target_value) }}"
                            step="0.01"
                            min="0"
                            required
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 pr-16"
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400">{{ $kpiMonthlyTarget->template->target_unit }}</span>
                        </div>
                    </div>
                    @error('target_value')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notes
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="3"
                        placeholder="Optional notes about this target..."
                        class="form-textarea w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >{{ old('notes', $kpiMonthlyTarget->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.kpi-monthly-targets.index', ['year' => $kpiMonthlyTarget->target_year]) }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Update Target
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-app-layout>
