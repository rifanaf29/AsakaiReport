<x-app-layout>
    <div class="px-6 pt-6 mb-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create KPI Entry') }}
        </h2>
    </div>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">How to Fill KPI Entry</h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Select a <strong>KPI Template</strong> (e.g., Production Output, Quality Rate)</li>
                                <li>Enter the <strong>Date</strong> for this KPI measurement</li>
                                <li>Enter <strong>Target</strong> value (your goal or target number)</li>
                                <li>Enter <strong>Actual</strong> value (the achieved result)</li>
                                <li>Fill any additional custom fields that appear</li>
                                <li>Add optional notes if needed</li>
                                <li>Submit - the system will automatically calculate if the KPI is OK or NG</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('kpi.entries.store') }}" id="kpiEntryForm">
                        @csrf

                        <!-- Template Selection -->
                        <div class="mb-6">
                            <label for="kpi_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                KPI Template <span class="text-red-500">*</span>
                            </label>
                            <select id="kpi_template_id" name="kpi_template_id" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    onchange="loadTemplateFields(this.value)">
                                <option value="">Select a template...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" 
                                            data-fields='@json($template->fields)'
                                            data-unit="{{ $template->target_unit }}"
                                            {{ old('kpi_template_id', $selectedTemplate?->id) == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }} ({{ $template->department->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('kpi_template_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Entry Date -->
                        <div class="mb-6">
                            <label for="entry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Entry Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="entry_date" name="entry_date" required readonly
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
                            @error('entry_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Target & Actual -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Target (<span id="target-unit">%</span>) <span class="text-red-500">*</span>
                                    <span id="target-status" class="text-xs text-gray-500"></span>
                                </label>
                                <div class="flex items-center">
                                    <input type="number" step="0.01" id="target" name="target" required
                                           value="{{ old('target') }}"
                                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <span id="target-unit-suffix" class="ml-2 text-sm font-medium text-gray-600 dark:text-gray-400">%</span>
                                </div>
                                @error('target')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Will auto-load from monthly target if set</p>
                            </div>

                            <div>
                                <label for="actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Actual (<span id="actual-unit">%</span>) <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center">
                                    <input type="number" step="0.01" id="actual" name="actual" required
                                           value="{{ old('actual') }}"
                                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <span id="actual-unit-suffix" class="ml-2 text-sm font-medium text-gray-600 dark:text-gray-400">%</span>
                                </div>
                                @error('actual')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Dynamic Fields Container -->
                        <div id="dynamic-fields-container" class="mb-6"></div>

                        <!-- Notes/Comments -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes/Comments
                            </label>
                            <textarea id="notes" name="notes" rows="3"
                                      placeholder="Add any additional notes or comments about this KPI entry..."
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('kpi.entries.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Create Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadTemplateFields(templateId) {
            const container = document.getElementById('dynamic-fields-container');
            container.innerHTML = '';

            if (!templateId) {
                // Reset unit to default
                updateUnit('%');
                return;
            }

            const select = document.getElementById('kpi_template_id');
            const option = select.options[select.selectedIndex];
            const fields = JSON.parse(option.dataset.fields || '[]');
            const unit = option.dataset.unit || '%';
            
            // Update unit display
            updateUnit(unit);
            
            // Always load monthly target when template changes
            loadMonthlyTarget();

            if (fields.length === 0) return;

            const heading = document.createElement('h3');
            heading.className = 'text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4';
            heading.textContent = 'Additional Fields';
            container.appendChild(heading);

            const grid = document.createElement('div');
            grid.className = 'grid grid-cols-1 md:grid-cols-2 gap-6';

            fields.forEach(field => {
                const fieldDiv = document.createElement('div');
                
                const label = document.createElement('label');
                label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
                label.textContent = field.field_name + (field.is_required ? ' *' : '');
                fieldDiv.appendChild(label);

                let input;
                switch(field.field_type) {
                    case 'text':
                        input = document.createElement('input');
                        input.type = 'text';
                        break;
                    case 'number':
                    case 'decimal':
                        input = document.createElement('input');
                        input.type = 'number';
                        input.step = '0.01';
                        break;
                    case 'date':
                        input = document.createElement('input');
                        input.type = 'date';
                        break;
                    case 'textarea':
                        input = document.createElement('textarea');
                        input.rows = 3;
                        break;
                }

                input.name = `dynamic_fields[${field.field_key}]`;
                input.required = field.is_required;
                input.className = 'w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500';

                fieldDiv.appendChild(input);
                grid.appendChild(fieldDiv);
            });

            container.appendChild(grid);
        }

        function updateUnit(unit) {
            document.getElementById('target-unit').textContent = unit;
            document.getElementById('target-unit-suffix').textContent = unit;
            document.getElementById('actual-unit').textContent = unit;
            document.getElementById('actual-unit-suffix').textContent = unit;
        }

        async function loadMonthlyTarget() {
            const templateId = document.getElementById('kpi_template_id').value;
            const entryDate = document.getElementById('entry_date').value;
            const targetInput = document.getElementById('target');
            const targetStatus = document.getElementById('target-status');

            console.log('loadMonthlyTarget called - templateId:', templateId, 'entryDate:', entryDate);

            if (!templateId || !entryDate) {
                console.log('Missing templateId or entryDate, skipping...');
                targetStatus.textContent = '';
                return;
            }

            // Show loading state
            targetStatus.textContent = '(loading...)';
            targetStatus.className = 'text-xs text-gray-500';

            try {
                const url = `{{ route('master.kpi-monthly-targets.get-target') }}?template_id=${templateId}&date=${entryDate}`;
                console.log('Fetching from:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                console.log('Response data:', data);

                if (data.has_target && data.target !== null && data.target !== undefined) {
                    // Always update the target value
                    targetInput.value = data.target;
                    targetStatus.textContent = '(from monthly target: ' + data.target + ')';
                    targetStatus.className = 'text-xs text-green-600 dark:text-green-400';
                    console.log('Target loaded:', data.target);
                } else {
                    targetStatus.textContent = '(no monthly target set)';
                    targetStatus.className = 'text-xs text-yellow-600 dark:text-yellow-400';
                    console.log('No target found for this date/template');
                }
            } catch (error) {
                console.error('Error loading monthly target:', error);
                targetStatus.textContent = '(failed to load target)';
                targetStatus.className = 'text-xs text-red-600 dark:text-red-400';
            }
        }

        // Load fields if template is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            const templateId = document.getElementById('kpi_template_id').value;
            if (templateId) {
                loadTemplateFields(templateId);
                loadMonthlyTarget();
            }
        });
    </script>
</x-app-layout>
