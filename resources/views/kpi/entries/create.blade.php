<x-app-layout>
    <div class="px-6 pt-6 mb-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create KPI Entry') }}
        </h2>
    </div>

    <div class="py-6">
        <div class="max-w mx-auto sm:px-6 lg:px-8">
            <!-- Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5 mb-6 shadow-sm">
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('kpi.entries.store') }}" id="kpiEntryForm">
                        @csrf

                        <!-- Template Selection -->
                        <div class="mb-6">
                            <label for="kpi_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                KPI Template <span class="text-red-500">*</span>
                            </label>
                            <select id="kpi_template_id" name="kpi_template_id" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
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
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
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
                                         class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                                         class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- CAPA Section -->
                        <div class="mb-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">CAPA (Corrective and Preventive Action)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Fill this section if issues are identified or when status is NG</p>
                            </div>

                            <!-- CAPA Area Information -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 mb-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">CAPA Area Information</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="capa_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            CAPA Date
                                        </label>
                                        <input type="date" id="capa_date" name="capa_areas[0][capa_date]"
                                               value="{{ date('Y-m-d') }}"
                                                 class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="capa_area_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Area Name
                                        </label>
                                        <select id="capa_area_select" 
                                                onchange="toggleCustomAreaInput()"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option value="">-- Select Area --</option>
                                            @foreach($capaAreas as $area)
                                                <option value="{{ $area }}">{{ $area }}</option>
                                            @endforeach
                                            <option value="__custom__">✏️ Enter Custom Area Name</option>
                                        </select>
                                        
                                        <input type="text" 
                                               id="capa_area_name" 
                                               name="capa_areas[0][area_name]"
                                               placeholder="Enter custom area name"
                                                 class="hidden mt-2 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select from existing areas or enter a new one</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="capa_area_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Area Description
                                        </label>
                                        <textarea id="capa_area_description" name="capa_areas[0][area_description]" rows="2"
                                                  placeholder="Brief description of this area..."
                                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Problems Section -->
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200">Problems</h4>
                                <button type="button" onclick="addProblem()" 
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Problem
                                </button>
                            </div>

                            <!-- Problems Container -->
                            <div id="problems-container"></div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('kpi.entries.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-lg">
                                Create Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle custom area name input
        function toggleCustomAreaInput() {
            const select = document.getElementById('capa_area_select');
            const input = document.getElementById('capa_area_name');
            
            if (select.value === '__custom__') {
                input.classList.remove('hidden');
                input.value = ''; // Clear the value to allow custom input
                input.focus();
            } else {
                input.classList.add('hidden');
                input.value = select.value; // Always set the value from dropdown
            }
        }

        // Ensure area name is set before form submission
        document.getElementById('kpiEntryForm').addEventListener('submit', function(e) {
            const select = document.getElementById('capa_area_select');
            const input = document.getElementById('capa_area_name');
            
            // If a dropdown value is selected (not custom), ensure it's in the hidden input
            if (select.value && select.value !== '__custom__') {
                input.value = select.value;
            }
        });

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
                input.className = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent';

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

        // CAPA Dynamic Form Functions
        let problemCounter = 0;

        function addProblem() {
            const container = document.getElementById('problems-container');
            const problemIndex = problemCounter++;
            
            const problemDiv = document.createElement('div');
            problemDiv.className = 'bg-white dark:bg-gray-800 rounded-xl p-5 mb-4 border border-gray-200 dark:border-gray-700 shadow-sm';
            problemDiv.id = `problem-${problemIndex}`;
            problemDiv.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">Problem #${problemIndex + 1}</h4>
                    <button type="button" onclick="removeProblem(${problemIndex})" 
                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Severity <span class="text-red-500">*</span>
                    </label>
                    <select name="capa_areas[0][problems][${problemIndex}][severity]" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Problem Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="capa_areas[0][problems][${problemIndex}][problem_description]" required rows="3"
                              placeholder="Describe the problem or issue identified..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>

                <div class="border-t border-gray-300 dark:border-gray-600 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Root Causes</h5>
                        <button type="button" onclick="addCause(${problemIndex})" 
                                class="text-sm px-3 py-1.5 bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Add Cause
                        </button>
                    </div>
                    <div id="causes-container-${problemIndex}"></div>
                </div>
            `;
            
            container.appendChild(problemDiv);
            addCause(problemIndex);
        }

        function removeProblem(problemIndex) {
            const problem = document.getElementById(`problem-${problemIndex}`);
            if (problem) {
                problem.remove();
            }
        }

        function addCause(problemIndex) {
            const container = document.getElementById(`causes-container-${problemIndex}`);
            const causeCount = container.children.length;
            
            const causeDiv = document.createElement('div');
            causeDiv.className = 'bg-gray-50 dark:bg-gray-900/20 rounded-xl p-4 mb-3 border border-gray-200 dark:border-gray-700';
            causeDiv.id = `cause-${problemIndex}-${causeCount}`;
            causeDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cause #${causeCount + 1}</h6>
                    <button type="button" onclick="removeCause(${problemIndex}, ${causeCount})" 
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Cause Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="capa_areas[0][problems][${problemIndex}][causes][${causeCount}][cause_description]" required rows="2"
                                  placeholder="What caused this problem?"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>
                </div>

                <div class="border-t border-gray-300 dark:border-gray-600 pt-3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Action Plans</span>
                        <button type="button" onclick="addActionPlan(${problemIndex}, ${causeCount})" 
                                class="text-xs px-3 py-1.5 bg-white dark:bg-gray-800 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            Add Action
                        </button>
                    </div>
                    <div id="actions-container-${problemIndex}-${causeCount}"></div>
                </div>
            `;
            
            container.appendChild(causeDiv);
            addActionPlan(problemIndex, causeCount);
        }

        function removeCause(problemIndex, causeIndex) {
            const cause = document.getElementById(`cause-${problemIndex}-${causeIndex}`);
            if (cause) {
                cause.remove();
            }
        }

        function addActionPlan(problemIndex, causeIndex) {
            const container = document.getElementById(`actions-container-${problemIndex}-${causeIndex}`);
            const actionCount = container.children.length;
            
            const actionDiv = document.createElement('div');
            actionDiv.className = 'bg-white dark:bg-gray-800 rounded-lg p-4 mb-2 border border-gray-200 dark:border-gray-700';
            actionDiv.id = `action-${problemIndex}-${causeIndex}-${actionCount}`;
            actionDiv.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Action #${actionCount + 1}</span>
                    <button type="button" onclick="removeAction(${problemIndex}, ${causeIndex}, ${actionCount})" 
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Action Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="capa_areas[0][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][description]" 
                                  required rows="2"
                                  placeholder="What action will be taken?"
                                  class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                PIC (Person In Charge) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="capa_areas[0][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][person_in_charge]"
                                   placeholder="Enter name..."
                                   required
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Due Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="capa_areas[0][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][due_date]"
                                   required
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="capa_areas[0][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][status]"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="open">Open</option>
                                <option value="progress">In Progress</option>
                                <option value="close">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes / Remarks
                        </label>
                        <input type="text" 
                               name="capa_areas[0][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][keterangan]"
                               placeholder="Additional notes..."
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            `;
            
            container.appendChild(actionDiv);
        }

        function removeAction(problemIndex, causeIndex, actionIndex) {
            const action = document.getElementById(`action-${problemIndex}-${causeIndex}-${actionIndex}`);
            if (action) {
                action.remove();
            }
        }
    </script>
</x-app-layout>
