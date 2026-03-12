<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('master.kpi-templates.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create KPI Template</h1>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.kpi-templates.store') }}" method="POST" class="p-6">
                @csrf

                <!-- Template Code -->
                <div class="mb-6">
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Template Code <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        value="{{ old('code') }}"
                        class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('code') border-red-500 @enderror"
                        placeholder="e.g., PROD_KPI_001"
                        required
                    >
                    @error('code')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unique identifier for this template</p>
                </div>


                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        rows="4"
                        class="form-textarea w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('description') border-red-500 @enderror"
                        placeholder="Brief description of this KPI template"
                    >{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actual Value Configuration -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Actual Value Source <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="actual_mode"
                                value="manual"
                                class="form-radio text-indigo-600"
                                {{ old('actual_mode', 'manual') === 'manual' ? 'checked' : '' }}
                                onchange="toggleActualMode(this.value)"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Manual input</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="actual_mode"
                                value="aggregated"
                                class="form-radio text-indigo-600"
                                {{ old('actual_mode') === 'aggregated' ? 'checked' : '' }}
                                onchange="toggleActualMode(this.value)"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aggregated from additional fields</span>
                        </label>
                    </div>

                    <div id="actual-aggregation-settings" class="mt-4 hidden">
                        <div class="mb-4">
                            <label for="actual_aggregation" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Aggregation
                            </label>
                            <select
                                id="actual_aggregation"
                                name="actual_aggregation"
                                class="form-select w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                            >
                                <option value="sum" {{ old('actual_aggregation', 'sum') === 'sum' ? 'selected' : '' }}>Sum</option>
                                <option value="avg" {{ old('actual_aggregation') === 'avg' ? 'selected' : '' }}>Average</option>
                                <option value="min" {{ old('actual_aggregation') === 'min' ? 'selected' : '' }}>Minimum</option>
                                <option value="max" {{ old('actual_aggregation') === 'max' ? 'selected' : '' }}>Maximum</option>
                                <option value="formula" {{ old('actual_aggregation') === 'formula' ? 'selected' : '' }}>Formula</option>
                            </select>

                            @error('actual_aggregation')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="actual-field-keys-wrapper">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Source Fields
                            </label>
                            <div
                                id="actual-field-keys"
                                data-selected='@json(old('actual_field_keys', []))'
                                class="grid grid-cols-1 sm:grid-cols-2 gap-2"
                            ></div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select numeric fields to calculate Actual</p>

                            <p id="actual-field-keys-client-error" class="mt-1 hidden text-sm text-red-500">
                                Please select at least one source field.
                            </p>
                            @error('actual_field_keys')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="actual-formula-wrapper" class="hidden mt-4">
                            <label for="actual_formula" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Actual Formula
                            </label>
                            <input
                                type="text"
                                name="actual_formula"
                                id="actual_formula"
                                value="{{ old('actual_formula') }}"
                                class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 @error('actual_formula') border-red-500 @enderror"
                                placeholder="e.g., =pd1/pd2*1000000"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use field keys (e.g., pd1, pd2). Supported: +, -, *, /, parentheses.</p>

                            <p id="actual-formula-client-error" class="mt-1 hidden text-sm text-red-500">
                                Please enter an actual formula.
                            </p>
                            @error('actual_formula')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @error('actual_mode')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Status -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1"
                            class="form-checkbox rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            {{ old('is_active', true) ? 'checked' : '' }}
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Inactive templates cannot be used for new entries</p>
                </div>

                <!-- Template Fields Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Template Fields (Optional)
                        </label>
                        <button type="button" onclick="addField()" class="btn-sm bg-indigo-600 hover:bg-indigo-700 text-white">
                            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Field
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Add custom fields for data collection (e.g., PD1, PD2, Shift, etc.)</p>
                    
                    <div id="fieldsContainer" class="space-y-3">
                        <!-- Dynamic fields will be added here -->
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.kpi-templates.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Create Template
                    </button>
                </div>

            </form>
        </div>

    </div>

    <script>
        let fieldIndex = 0;

        function addField() {
            const container = document.getElementById('fieldsContainer');
            const fieldHtml = `
                <div class="field-row bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700" data-index="${fieldIndex}">
                    <div class="grid grid-cols-12 gap-3 items-start">
                        <!-- Field Name -->
                        <div class="col-span-12 sm:col-span-3">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Field Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="fields[${fieldIndex}][field_name]" 
                                placeholder="e.g., PD1, Shift"
                                class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-0.5">Display name</p>
                        </div>

                        <!-- Field Key -->
                        <div class="col-span-12 sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Field Key <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="fields[${fieldIndex}][field_key]" 
                                placeholder="e.g., pd1"
                                class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-0.5">Unique key</p>
                        </div>

                        <!-- Field Type -->
                        <div class="col-span-12 sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="fields[${fieldIndex}][field_type]" 
                                class="form-select w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                required
                                onchange="toggleCalculationField(${fieldIndex}, this.value)"
                            >
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="decimal">Decimal</option>
                                <option value="accounting">Accounting</option>
                                <option value="date">Date</option>
                                <option value="calculated">Calculated</option>
                            </select>
                        </div>

                        <!-- Unit -->
                        <div class="col-span-6 sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Unit
                            </label>
                            <input 
                                type="text" 
                                name="fields[${fieldIndex}][unit]" 
                                placeholder="%, pcs, kg"
                                maxlength="20"
                                class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                            >
                        </div>

                        <!-- Sort Order -->
                        <div class="col-span-6 sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Order
                            </label>
                            <input 
                                type="number" 
                                name="fields[${fieldIndex}][sort_order]" 
                                value="${fieldIndex + 1}"
                                min="1"
                                class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                            >
                        </div>

                        <!-- Checkboxes -->
                        <div class="col-span-6 sm:col-span-2 space-y-1">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="fields[${fieldIndex}][is_required]" 
                                    value="1"
                                    class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-xs"
                                >
                                <span class="ml-1 text-xs text-gray-700 dark:text-gray-300">Required</span>
                            </label>
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="fields[${fieldIndex}][is_editable]" 
                                    value="1"
                                    checked
                                    class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-xs"
                                >
                                <span class="ml-1 text-xs text-gray-700 dark:text-gray-300">Editable</span>
                            </label>
                        </div>

                        <!-- Remove Button -->
                        <div class="col-span-12 flex justify-between items-center">
                            <!-- Calculation Formula (hidden by default) -->
                            <div id="calc-field-${fieldIndex}" class="flex-1 mr-2 hidden">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Calculation Formula
                                </label>
                                <input 
                                    type="text" 
                                    name="fields[${fieldIndex}][calculation_formula]" 
                                    placeholder="e.g., AVG(pd1,pd2,pd3) or SUM(pd1,pd2)"
                                    class="form-input w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                >
                                <p class="text-xs text-gray-500 mt-0.5">Use field keys in formula</p>
                            </div>
                            <button 
                                type="button" 
                                onclick="removeField(${fieldIndex})" 
                                class="btn-sm border-red-200 hover:border-red-300 text-red-600 dark:text-red-400"
                                title="Remove field"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHtml);
            bindFieldRowEvents(fieldIndex);
            updateActualFieldOptions();
            fieldIndex++;
        }

        function removeField(index) {
            const fieldRow = document.querySelector(`[data-index="${index}"]`);
            if (fieldRow) {
                fieldRow.remove();
            }
            updateActualFieldOptions();
        }

        function toggleCalculationField(index, fieldType) {
            const calcField = document.getElementById(`calc-field-${index}`);
            if (calcField) {
                if (fieldType === 'calculated') {
                    calcField.classList.remove('hidden');
                } else {
                    calcField.classList.add('hidden');
                }
            }
        }

        function toggleActualMode(mode) {
            const settings = document.getElementById('actual-aggregation-settings');
            if (settings) {
                settings.classList.toggle('hidden', mode !== 'aggregated');
            }

            toggleActualAggregationUI();

            validateActualAggregationSelection();
        }

        function toggleActualAggregationUI() {
            const mode = document.querySelector('input[name="actual_mode"]:checked')?.value;
            if (mode !== 'aggregated') return;

            const aggregation = document.getElementById('actual_aggregation')?.value;
            const fieldWrapper = document.getElementById('actual-field-keys-wrapper');
            const formulaWrapper = document.getElementById('actual-formula-wrapper');

            if (fieldWrapper) {
                fieldWrapper.classList.toggle('hidden', aggregation === 'formula');
            }
            if (formulaWrapper) {
                formulaWrapper.classList.toggle('hidden', aggregation !== 'formula');
            }
        }

        function validateActualAggregationSelection() {
            const mode = document.querySelector('input[name="actual_mode"]:checked')?.value;
            const clientError = document.getElementById('actual-field-keys-client-error');
            const formulaClientError = document.getElementById('actual-formula-client-error');

            if (!clientError) return true;

            if (mode !== 'aggregated') {
                clientError.classList.add('hidden');
                if (formulaClientError) formulaClientError.classList.add('hidden');
                return true;
            }

            const aggregation = document.getElementById('actual_aggregation')?.value;
            if (aggregation === 'formula') {
                clientError.classList.add('hidden');

                const formulaInput = document.getElementById('actual_formula');
                const ok = !!(formulaInput && formulaInput.value.trim().length > 0);
                if (formulaClientError) formulaClientError.classList.toggle('hidden', ok);
                return ok;
            }

            if (formulaClientError) formulaClientError.classList.add('hidden');

            const container = document.getElementById('actual-field-keys');
            const checkedCount = container
                ? container.querySelectorAll('input[type="checkbox"][name="actual_field_keys[]"]:checked').length
                : 0;

            const ok = checkedCount > 0;
            clientError.classList.toggle('hidden', ok);
            return ok;
        }

        function bindFieldRowEvents(index) {
            const row = document.querySelector(`[data-index="${index}"]`);
            if (!row) return;

            const nameInput = row.querySelector(`input[name="fields[${index}][field_name]"]`);
            const keyInput = row.querySelector(`input[name="fields[${index}][field_key]"]`);

            [nameInput, keyInput].forEach((input) => {
                if (input) {
                    input.addEventListener('input', updateActualFieldOptions);
                }
            });
        }

        function updateActualFieldOptions() {
            const container = document.getElementById('actual-field-keys');
            if (!container) return;

            const selected = new Set(
                Array.from(container.querySelectorAll('input[type="checkbox"]'))
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value)
            );

            if (selected.size === 0 && container.dataset.selected) {
                try {
                    const initialSelected = JSON.parse(container.dataset.selected);
                    initialSelected.forEach((value) => selected.add(value));
                    container.dataset.selected = '';
                } catch (error) {
                    // Ignore invalid JSON
                }
            }

            const fieldRows = Array.from(document.querySelectorAll('#fieldsContainer [data-index]'));
            const fields = fieldRows.map((row) => {
                const index = row.getAttribute('data-index');
                const nameInput = row.querySelector(`input[name="fields[${index}][field_name]"]`);
                const keyInput = row.querySelector(`input[name="fields[${index}][field_key]"]`);
                const fieldName = nameInput ? nameInput.value.trim() : '';
                const fieldKey = keyInput ? keyInput.value.trim() : '';
                return { fieldName, fieldKey };
            }).filter((field) => field.fieldKey.length > 0);

            container.innerHTML = '';

            if (fields.length === 0) {
                container.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">Add fields to choose sources.</span>';
                return;
            }

            fields.forEach((field) => {
                const label = document.createElement('label');
                label.className = 'flex items-center text-sm text-gray-700 dark:text-gray-300';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'actual_field_keys[]';
                checkbox.value = field.fieldKey;
                checkbox.className = 'form-checkbox rounded border-gray-300 dark:border-gray-600';
                checkbox.checked = selected.has(field.fieldKey);

                const text = document.createElement('span');
                text.className = 'ml-2';
                text.textContent = field.fieldName ? `${field.fieldName} (${field.fieldKey})` : field.fieldKey;

                label.appendChild(checkbox);
                label.appendChild(text);
                container.appendChild(label);
            });

            validateActualAggregationSelection();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedMode = document.querySelector('input[name="actual_mode"]:checked');
            toggleActualMode(selectedMode ? selectedMode.value : 'manual');
            updateActualFieldOptions();

            toggleActualAggregationUI();

            const form = document.querySelector('form[action="{{ route('master.kpi-templates.store') }}"]');
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (!validateActualAggregationSelection()) {
                        event.preventDefault();
                    }
                });
            }

            const actualAggregationSelect = document.getElementById('actual_aggregation');
            if (actualAggregationSelect) {
                actualAggregationSelect.addEventListener('change', function () {
                    toggleActualAggregationUI();
                    validateActualAggregationSelection();
                });
            }

            const actualFormulaInput = document.getElementById('actual_formula');
            if (actualFormulaInput) {
                actualFormulaInput.addEventListener('input', function () {
                    validateActualAggregationSelection();
                });
            }

            document.addEventListener('change', function (event) {
                const target = event.target;
                if (target && target.matches('input[type="checkbox"][name="actual_field_keys[]"]')) {
                    validateActualAggregationSelection();
                }
            });
        });
    </script>
</x-app-layout>
