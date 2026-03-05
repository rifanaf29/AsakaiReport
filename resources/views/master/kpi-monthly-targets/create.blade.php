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
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Set Yearly Target</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Set one fixed target for the selected year</p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.kpi-monthly-targets.store') }}" method="POST" class="p-6" id="targetForm">
                @csrf

                <!-- Department Selection -->
                <div class="mb-6">
                    <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Department <span class="text-red-500">*</span>
                    </label>
                    <select id="department_id" name="department_id" required
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            onchange="reloadWithParams()">
                        <option value="">Select a department...</option>
                        @foreach(App\Models\Department::active()->orderBy('name')->get() as $dept)
                        <option value="{{ $dept->id }}" {{ (string) ($departmentId ?? '') === (string) $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->code }})
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- KPI Selection -->
                <div class="mb-6">
                    <label for="kpi_definition_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        KPI <span class="text-red-500">*</span>
                    </label>
                    <select id="kpi_definition_id" name="kpi_definition_id" required
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            onchange="reloadWithParams()">
                        <option value="">Select a KPI...</option>
                        @foreach($kpis as $kpi)
                            @php
                                $kpiName = $kpi->display_name ?: ($kpi->template?->code ?: 'KPI');
                                $tplCode = $kpi->template?->code;
                            @endphp
                            <option value="{{ $kpi->id }}" {{ $kpiDefinition && $kpiDefinition->id == $kpi->id ? 'selected' : '' }}>
                                {{ $kpiName }}{{ $tplCode ? ' ('.$tplCode.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('kpi_definition_id')
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

                @if($kpiDefinition)
                <!-- Yearly Target Value -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Target for {{ $year }}
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        This target will be used for all KPI entries in {{ $year }}.
                    </p>

                    <!-- Target Unit -->
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
                        <label for="target_unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Unit <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="target_unit"
                            name="target_unit"
                            value="{{ old('target_unit', $existingTargetUnit ?? '%') }}"
                            required
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 @error('target_unit') border-red-500 @enderror"
                            placeholder="e.g., %, pcs, kg, Day"
                            oninput="syncTargetUnitSuffix()"
                        >
                        @error('target_unit')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This unit is defined per year (yearly target), not per template.</p>
                    </div>

                    <!-- Target Logic -->
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
                        <label for="target_operator" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Target Logic <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="target_operator"
                            name="target_operator"
                            required
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('target_operator') border-red-500 @enderror"
                        >
                            <option value="gte" {{ old('target_operator', $existingTargetOperator ?? 'gte') === 'gte' ? 'selected' : '' }}>Higher is better (Actual ≥ Target)</option>
                            <option value="lte" {{ old('target_operator', $existingTargetOperator ?? 'gte') === 'lte' ? 'selected' : '' }}>Lower is better (Actual ≤ Target)</option>
                        </select>
                        @error('target_operator')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <label for="target_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Target Value <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                id="target_value"
                                name="target_value"
                                value="{{ old('target_value', $existingTargetValue ?? '') }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                                class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 pr-12"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span id="target-unit-suffix" class="text-gray-500 dark:text-gray-400 text-sm">{{ old('target_unit', $existingTargetUnit ?? '%') }}</span>
                            </div>
                        </div>
                        @error('target_value')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
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
                            <p>When users create KPI entries, the target value will automatically load based on the entry date's year. This ensures consistency across all entries for the year.</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.kpi-monthly-targets.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Save Target
                    </button>
                </div>
                @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Please select a KPI to set a yearly target
                </div>
                @endif

            </form>
        </div>

    </div>

    <script>
        function syncTargetUnitSuffix() {
            const input = document.getElementById('target_unit');
            const suffix = document.getElementById('target-unit-suffix');
            if (!suffix) return;
            suffix.textContent = (input && input.value) ? input.value : '%';
        }

        function reloadWithParams() {
            const kpiDefinitionId = document.getElementById('kpi_definition_id').value;
            const departmentId = document.getElementById('department_id').value;
            const year = document.getElementById('target_year').value;

            if (departmentId && year) {
                const kpiPart = kpiDefinitionId ? `&kpi_definition_id=${kpiDefinitionId}` : '';
                window.location.href = `{{ route('master.kpi-monthly-targets.create') }}?department=${departmentId}&year=${year}${kpiPart}`;
            }
        }

        document.addEventListener('DOMContentLoaded', syncTargetUnitSuffix);
    </script>
</x-app-layout>
