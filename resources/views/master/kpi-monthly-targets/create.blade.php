<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-5xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('master.kpi-monthly-targets.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Set Monthly Targets</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Set fixed targets for all 12 months of the year</p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.kpi-monthly-targets.store') }}" method="POST" class="p-6" id="targetForm">
                @csrf

                <!-- Template Selection -->
                <div class="mb-6">
                    <label for="kpi_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        KPI Template <span class="text-red-500">*</span>
                    </label>
                    <select id="kpi_template_id" name="kpi_template_id" required
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            onchange="reloadWithParams()">
                        <option value="">Select a template...</option>
                        @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ $template && $template->id == $tpl->id ? 'selected' : '' }}>
                            {{ $tpl->name }} ({{ $tpl->department->name }})
                        </option>
                        @endforeach
                    </select>
                    @error('kpi_template_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Year Selection -->
                <div class="mb-6">
                    <label for="target_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Year <span class="text-red-500">*</span>
                    </label>
                    <select id="target_year" name="target_year" required
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            onchange="reloadWithParams()">
                        @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('target_year')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @if($template)
                <!-- Monthly Targets Grid -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Monthly Targets for {{ $year }}
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Enter target values for each month. Leave blank for months without targets.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $monthIndex => $monthName)
                        @php($monthNum = $monthIndex + 1)
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <label for="target_{{ $monthNum }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $monthName }}
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    id="target_{{ $monthNum }}" 
                                    name="targets[{{ $monthNum }}]" 
                                    value="{{ old('targets.' . $monthNum, $existingTargets[$monthNum] ?? '') }}"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 pr-12"
                                >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $template->target_unit }}</span>
                                </div>
                            </div>
                            @error('targets.' . $monthNum)
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-medium mb-1">About Monthly Targets</p>
                            <p>When users create KPI entries, the target value will automatically load based on the entry date's month. This ensures consistency across all entries for the month.</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.kpi-monthly-targets.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Save Targets
                    </button>
                </div>
                @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Please select a template to set monthly targets
                </div>
                @endif

            </form>
        </div>

    </div>

    <script>
        function reloadWithParams() {
            const templateId = document.getElementById('kpi_template_id').value;
            const year = document.getElementById('target_year').value;
            
            if (templateId && year) {
                window.location.href = `{{ route('master.kpi-monthly-targets.create') }}?template_id=${templateId}&year=${year}`;
            }
        }
    </script>
</x-app-layout>
