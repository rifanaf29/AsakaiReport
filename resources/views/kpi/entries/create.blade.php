<x-app-layout>
    <div class="px-6 pt-6 mb-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create KPI Entry') }}
        </h2>
    </div>

    <div class="py-6">
        <div class="max-w mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-5 py-4 rounded-xl shadow-sm">
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-5 py-4 rounded-xl shadow-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
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
                                <li>Select a <strong>Department</strong></li>
                                <li>Choose the <strong>Entry Date</strong> once (applies to all KPIs below)</li>
                                <li>Fill the KPIs you want to submit (Target / Actual / Additional Fields / Notes / CAPA)</li>
                                <li>Submit once to create multiple KPI entries</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('kpi.entries.store') }}" id="kpiEntryForm">
                        @csrf

                        @if(auth()->user()->can_access_all_departments)
                        <!-- Department Selection -->
                        <div class="mb-6">
                            <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <select id="department_id" name="department_id" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select a department...</option>
                                @foreach(App\Models\Department::active()->orderBy('name')->get() as $dept)
                                    <option value="{{ $dept->id }}" {{ (string) old('department_id', $selectedDepartmentId ?? '') === (string) $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif

                        <!-- Entry Date -->
                        <div class="mb-6">
                            <label for="entry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Entry Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="entry_date" name="entry_date" required
                                   value="{{ old('entry_date', date('Y-m-d')) }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('entry_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($kpis->isEmpty())
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                {{ auth()->user()->can_access_all_departments && empty($selectedDepartmentId) ? 'Select a department first to load KPIs.' : 'No KPIs set for this department.' }}
                            </div>
                        @else
                            <div class="space-y-6" id="kpi-batch-container"
                                 data-department-id="{{ (int) ($selectedDepartmentId ?? 0) }}">
                                @foreach($kpis as $kpi)
                                    @php
                                        $kpiId = (int) $kpi->id;
                                        $tpl = $kpi->template;
                                        $kpiLabel = $kpi->display_name ?: ($tpl?->code ?? ('KPI #' . $kpiId));
                                    @endphp

                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm bg-white dark:bg-gray-800"
                                         data-kpi-id="{{ $kpiId }}"
                                         data-actual-mode="{{ $tpl?->actual_mode ?? 'manual' }}"
                                         data-actual-aggregation="{{ $tpl?->actual_aggregation ?? '' }}"
                                         data-actual-field-keys='@json($tpl?->actual_field_keys ?? [])'>
                                        <div class="flex items-start justify-between gap-4 mb-4">
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">KPI</div>
                                                <div class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $kpiLabel }}</div>
                                                @if($tpl)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Template: {{ $tpl->code }}</div>
                                                @endif
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400" data-target-status></div>
                                        </div>

                                        <input type="hidden" name="entries[{{ $kpiId }}][kpi_definition_id]" value="{{ $kpiId }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Target (<span data-unit-label>%</span>)
                                                </label>
                                                <div class="flex items-center">
                                                    <input type="number" step="0.01" name="entries[{{ $kpiId }}][target]"
                                                           value="{{ old('entries.' . $kpiId . '.target') }}"
                                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                           data-target-input>
                                                    <span class="ml-2 text-sm font-medium text-gray-600 dark:text-gray-400" data-unit-suffix>%</span>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Actual (<span data-unit-label>%</span>)
                                                </label>
                                                <div class="flex items-center">
                                                    <input type="number" step="0.01" name="entries[{{ $kpiId }}][actual]"
                                                           value="{{ old('entries.' . $kpiId . '.actual') }}"
                                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                           data-actual-input>
                                                    <span class="ml-2 text-sm font-medium text-gray-600 dark:text-gray-400" data-unit-suffix>%</span>
                                                </div>
                                            </div>
                                        </div>

                                        @if($tpl && $tpl->fields && $tpl->fields->count())
                                            <div class="mb-4">
                                                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">Additional Fields</h3>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    @foreach($tpl->fields->where('field_type', '!=', 'calculated') as $field)
                                                        @php
                                                            $fieldKey = $field->field_key;
                                                            $oldVal = old('entries.' . $kpiId . '.dynamic_fields.' . $fieldKey);
                                                        @endphp
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                                {{ $field->field_name }}@if($field->is_required) <span class="text-red-500">*</span>@endif
                                                            </label>
                                                            @switch($field->field_type)
                                                                @case('textarea')
                                                                    <textarea rows="3"
                                                                              name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                                                                              @if($field->is_required) required @endif
                                                                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                                              data-dynamic-field data-field-key="{{ $fieldKey }}">{{ $oldVal }}</textarea>
                                                                    @break
                                                                @case('date')
                                                                    <input type="date"
                                                                           name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                                                                           value="{{ $oldVal }}"
                                                                           @if($field->is_required) required @endif
                                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                                           data-dynamic-field data-field-key="{{ $fieldKey }}">
                                                                    @break
                                                                @case('number')
                                                                @case('decimal')
                                                                    <input type="number" step="0.01"
                                                                           name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                                                                           value="{{ $oldVal }}"
                                                                           @if($field->is_required) required @endif
                                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                                           data-dynamic-field data-field-key="{{ $fieldKey }}">
                                                                    @break
                                                                @default
                                                                    <input type="text"
                                                                           name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                                                                           value="{{ $oldVal }}"
                                                                           @if($field->is_required) required @endif
                                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                                           data-dynamic-field data-field-key="{{ $fieldKey }}">
                                                            @endswitch
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes/Comments</label>
                                            <textarea rows="3"
                                                      name="entries[{{ $kpiId }}][notes]"
                                                      placeholder="Add any additional notes or comments about this KPI entry..."
                                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('entries.' . $kpiId . '.notes') }}</textarea>
                                        </div>

                                        <!-- CAPA Section (per KPI) -->
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                            <div class="mb-3">
                                                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">CAPA</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Fill if issues are identified or when status is NG</p>
                                            </div>

                                            <div class="mb-4">
                                                <button type="button" data-add-area
                                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors shadow-sm">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Add CAPA Area
                                                </button>
                                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">CAPA is hidden until you add an area</span>
                                            </div>

                                            <div data-capa-areas-container class="space-y-4 hidden"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('kpi.entries.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-lg">
                                Create Entries
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const departmentSelect = document.getElementById('department_id');
        if (departmentSelect) {
            departmentSelect.addEventListener('change', () => {
                const departmentId = departmentSelect.value;
                const url = new URL(window.location.href);
                if (departmentId) {
                    url.searchParams.set('department_id', departmentId);
                } else {
                    url.searchParams.delete('department_id');
                }
                window.location.href = url.toString();
            });
        }

        function getDepartmentId() {
            if (document.getElementById('department_id')) {
                return document.getElementById('department_id').value;
            }
            const container = document.getElementById('kpi-batch-container');
            return container ? container.dataset.departmentId : '';
        }

        function updateKpiUnit(card, unit) {
            card.querySelectorAll('[data-unit-label]').forEach((el) => { el.textContent = unit; });
            card.querySelectorAll('[data-unit-suffix]').forEach((el) => { el.textContent = unit; });
        }

        function setTargetStatus(card, text, kind = 'muted') {
            const el = card.querySelector('[data-target-status]');
            if (!el) return;
            el.textContent = text;
            if (kind === 'success') el.className = 'text-xs text-green-600 dark:text-green-400';
            else if (kind === 'warning') el.className = 'text-xs text-yellow-600 dark:text-yellow-400';
            else if (kind === 'error') el.className = 'text-xs text-red-600 dark:text-red-400';
            else el.className = 'text-xs text-gray-500 dark:text-gray-400';
        }

        function parseJson(value, fallback) {
            if (!value) return fallback;
            try {
                const parsed = JSON.parse(value);
                return parsed ?? fallback;
            } catch (e) {
                return fallback;
            }
        }

        function applyActualMode(card) {
            const actualMode = card.dataset.actualMode || 'manual';
            const actualInput = card.querySelector('[data-actual-input]');
            if (!actualInput) return;

            if (actualMode === 'aggregated') {
                actualInput.readOnly = true;
                actualInput.required = false;
                actualInput.classList.add('bg-gray-100', 'dark:bg-gray-600', 'cursor-not-allowed');
                computeAggregatedActual(card);
            } else {
                actualInput.readOnly = false;
                actualInput.required = false;
                actualInput.classList.remove('bg-gray-100', 'dark:bg-gray-600', 'cursor-not-allowed');
            }
        }

        function computeAggregatedActual(card) {
            const actualMode = card.dataset.actualMode || 'manual';
            if (actualMode !== 'aggregated') return;

            const aggregation = card.dataset.actualAggregation || 'sum';
            const fieldKeys = parseJson(card.dataset.actualFieldKeys, []);
            const actualInput = card.querySelector('[data-actual-input]');
            if (!actualInput) return;

            const kpiId = card.dataset.kpiId;
            const values = fieldKeys
                .map((key) => {
                    const selector = `[name="entries[${kpiId}][dynamic_fields][${key}]"]`;
                    const input = card.querySelector(selector);
                    return input ? parseFloat(input.value) : NaN;
                })
                .filter((value) => Number.isFinite(value));

            if (values.length === 0) {
                actualInput.value = '';
                return;
            }

            let result = 0;
            switch (aggregation) {
                case 'avg':
                    result = values.reduce((sum, value) => sum + value, 0) / values.length;
                    break;
                case 'min':
                    result = Math.min(...values);
                    break;
                case 'max':
                    result = Math.max(...values);
                    break;
                default:
                    result = values.reduce((sum, value) => sum + value, 0);
                    break;
            }

            actualInput.value = Number.isFinite(result) ? result.toFixed(2) : '';
        }

        async function loadYearlyTargetForCard(card) {
            const kpiDefinitionId = card.dataset.kpiId;
            const departmentId = getDepartmentId();
            const entryDate = document.getElementById('entry_date')?.value;
            const targetInput = card.querySelector('[data-target-input]');

            if (!kpiDefinitionId || !departmentId || !entryDate) {
                setTargetStatus(card, '', 'muted');
                return;
            }

            setTargetStatus(card, '(loading...)', 'muted');

            try {
                const url = `{{ route('master.kpi-monthly-targets.get-target') }}?kpi_definition_id=${kpiDefinitionId}&department_id=${departmentId}&date=${entryDate}`;
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                const data = await response.json();

                updateKpiUnit(card, data?.target_unit ? data.target_unit : '%');

                if (data?.has_target && data.target !== null && data.target !== undefined) {
                    const shouldAutofill = targetInput && (!targetInput.value || targetInput.dataset.autofilled === '1');
                    if (shouldAutofill) {
                        targetInput.value = data.target;
                        targetInput.dataset.autofilled = '1';
                    }
                    setTargetStatus(card, `(from yearly target: ${data.target})`, 'success');
                } else {
                    setTargetStatus(card, '(no yearly target set)', 'warning');
                }
            } catch (error) {
                setTargetStatus(card, '(failed to load target)', 'error');
            }
        }

        async function loadYearlyTargetsForAllCards() {
            const container = document.getElementById('kpi-batch-container');
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('[data-kpi-id]'));
            await Promise.all(cards.map((card) => loadYearlyTargetForCard(card)));
        }

        // CAPA builder (per KPI, supports multiple areas)
        const capaAreaCounters = {};
        const capaProblemCounters = {};

        function getCapaPrefix(kpiId, areaIndex) {
            return `entries[${kpiId}][capa_areas][${areaIndex}]`;
        }

        function getCapaAreasContainer(kpiId) {
            return document.querySelector(`[data-kpi-id="${kpiId}"] [data-capa-areas-container]`);
        }

        function toggleCustomAreaInput(kpiId, areaIndex, selectEl) {
            const input = document.getElementById(`capa_area_name_${kpiId}_${areaIndex}`);
            if (!input) return;

            if (selectEl.value === '__custom__') {
                input.classList.remove('hidden');
                input.value = '';
                input.focus();
            } else {
                input.classList.add('hidden');
                input.value = selectEl.value;
            }
        }

        function addCapaArea(kpiId) {
            const areasContainer = getCapaAreasContainer(kpiId);
            if (!areasContainer) return;

            if (capaAreaCounters[kpiId] === undefined) capaAreaCounters[kpiId] = 0;
            const areaIndex = capaAreaCounters[kpiId]++;
            const prefix = getCapaPrefix(kpiId, areaIndex);
            const entryDate = document.getElementById('entry_date')?.value || '';

            areasContainer.classList.remove('hidden');

            const areaDiv = document.createElement('div');
            areaDiv.className = 'bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm';
            areaDiv.id = `capa-area-${kpiId}-${areaIndex}`;
            areaDiv.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">CAPA Area #${areaIndex + 1}</h4>
                    <button type="button" data-remove-area data-kpi-id="${kpiId}" data-area-index="${areaIndex}"
                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CAPA Date</label>
                        <input type="date" name="${prefix}[capa_date]" value="${entryDate}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Area Name</label>
                        <select class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                data-area-select data-kpi-id="${kpiId}" data-area-index="${areaIndex}">
                            <option value="">-- Select Area --</option>
                            ${getAreaOptionsHtml()}
                            <option value="__custom__">✏️ Enter Custom Area Name</option>
                        </select>

                        <input type="text" id="capa_area_name_${kpiId}_${areaIndex}" name="${prefix}[area_name]"
                               placeholder="Enter custom area name"
                               class="hidden mt-2 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select from existing areas or enter a new one</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Area Description</label>
                        <textarea rows="2" name="${prefix}[area_description]"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-md font-semibold text-gray-700 dark:text-gray-200">Problems</h5>
                    <button type="button" data-add-problem data-kpi-id="${kpiId}" data-area-index="${areaIndex}"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Problem
                    </button>
                </div>

                <div data-problems-container data-kpi-id="${kpiId}" data-area-index="${areaIndex}"></div>
            `;

            areasContainer.appendChild(areaDiv);
        }

        function removeCapaArea(kpiId, areaIndex) {
            const area = document.getElementById(`capa-area-${kpiId}-${areaIndex}`);
            if (area) area.remove();

            const container = getCapaAreasContainer(kpiId);
            if (container && container.children.length === 0) {
                container.classList.add('hidden');
            }
        }

        function getAreaOptionsHtml() {
            const options = [];
            @foreach($capaAreas as $area)
                options.push(`<option value="{{ $area }}">{{ $area }}</option>`);
            @endforeach
            return options.join('');
        }

        function addProblem(kpiId, areaIndex) {
            const container = document.querySelector(`[data-problems-container][data-kpi-id="${kpiId}"][data-area-index="${areaIndex}"]`);
            if (!container) return;

            const counterKey = `${kpiId}-${areaIndex}`;
            if (capaProblemCounters[counterKey] === undefined) capaProblemCounters[counterKey] = 0;
            const problemIndex = capaProblemCounters[counterKey]++;
            const prefix = getCapaPrefix(kpiId, areaIndex);

            const problemDiv = document.createElement('div');
            problemDiv.className = 'bg-white dark:bg-gray-800 rounded-xl p-5 mb-4 border border-gray-200 dark:border-gray-700 shadow-sm';
            problemDiv.id = `problem-${kpiId}-${areaIndex}-${problemIndex}`;
            problemDiv.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">Problem #${problemIndex + 1}</h4>
                    <button type="button" data-remove-problem data-kpi-id="${kpiId}" data-area-index="${areaIndex}" data-problem-index="${problemIndex}"
                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Severity <span class="text-red-500">*</span></label>
                    <select name="${prefix}[problems][${problemIndex}][severity]" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Problem Description <span class="text-red-500">*</span></label>
                    <textarea name="${prefix}[problems][${problemIndex}][problem_description]" required rows="3"
                              placeholder="Describe the problem or issue identified..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>

                <div class="border-t border-gray-300 dark:border-gray-600 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Root Causes</h5>
                        <button type="button" data-add-cause data-kpi-id="${kpiId}" data-area-index="${areaIndex}" data-problem-index="${problemIndex}"
                                class="text-sm px-3 py-1.5 bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Add Cause
                        </button>
                    </div>
                    <div id="causes-container-${kpiId}-${areaIndex}-${problemIndex}"></div>
                </div>
            `;

            container.appendChild(problemDiv);
            addCause(kpiId, areaIndex, problemIndex);
        }

        function removeProblem(kpiId, areaIndex, problemIndex) {
            const problem = document.getElementById(`problem-${kpiId}-${areaIndex}-${problemIndex}`);
            if (problem) problem.remove();
        }

        function addCause(kpiId, areaIndex, problemIndex) {
            const container = document.getElementById(`causes-container-${kpiId}-${areaIndex}-${problemIndex}`);
            if (!container) return;
            const causeIndex = container.children.length;
            const prefix = getCapaPrefix(kpiId, areaIndex);

            const causeDiv = document.createElement('div');
            causeDiv.className = 'bg-gray-50 dark:bg-gray-900/20 rounded-xl p-4 mb-3 border border-gray-200 dark:border-gray-700';
            causeDiv.id = `cause-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}`;
            causeDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cause #${causeIndex + 1}</h6>
                    <button type="button" data-remove-cause data-kpi-id="${kpiId}" data-area-index="${areaIndex}" data-problem-index="${problemIndex}" data-cause-index="${causeIndex}"
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cause Description <span class="text-red-500">*</span></label>
                        <textarea name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][cause_description]" required rows="2"
                                  placeholder="What caused this problem?"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>
                </div>

                <div class="border-t border-gray-300 dark:border-gray-600 pt-3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Action Plans</span>
                        <button type="button" data-add-action data-kpi-id="${kpiId}" data-area-index="${areaIndex}" data-problem-index="${problemIndex}" data-cause-index="${causeIndex}"
                                class="text-xs px-3 py-1.5 bg-white dark:bg-gray-800 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            Add Action
                        </button>
                    </div>
                    <div id="actions-container-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}"></div>
                </div>
            `;

            container.appendChild(causeDiv);
            addActionPlan(kpiId, areaIndex, problemIndex, causeIndex);
        }

        function removeCause(kpiId, areaIndex, problemIndex, causeIndex) {
            const cause = document.getElementById(`cause-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}`);
            if (cause) cause.remove();
        }

        function addActionPlan(kpiId, areaIndex, problemIndex, causeIndex) {
            const container = document.getElementById(`actions-container-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}`);
            if (!container) return;
            const actionIndex = container.children.length;
            const prefix = getCapaPrefix(kpiId, areaIndex);

            const actionDiv = document.createElement('div');
            actionDiv.className = 'bg-white dark:bg-gray-800 rounded-lg p-4 mb-2 border border-gray-200 dark:border-gray-700';
            actionDiv.id = `action-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}-${actionIndex}`;
            actionDiv.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Action #${actionIndex + 1}</span>
                    <button type="button" data-remove-action data-kpi-id="${kpiId}" data-area-index="${areaIndex}" data-problem-index="${problemIndex}" data-cause-index="${causeIndex}" data-action-index="${actionIndex}"
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Action Description <span class="text-red-500">*</span></label>
                        <textarea name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionIndex}][description]" required rows="2"
                                  placeholder="What action will be taken?"
                                  class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">PIC (Person In Charge) <span class="text-red-500">*</span></label>
                            <input type="text" required
                                   name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionIndex}][person_in_charge]"
                                   placeholder="Enter name..."
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date <span class="text-red-500">*</span></label>
                            <input type="date" required
                                   name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionIndex}][due_date]"
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                            <select required
                                    name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionIndex}][status]"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="open">Open</option>
                                <option value="progress">In Progress</option>
                                <option value="close">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes / Remarks</label>
                        <input type="text"
                               name="${prefix}[problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionIndex}][keterangan]"
                               placeholder="Additional notes..."
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            `;

            container.appendChild(actionDiv);
        }

        function removeAction(kpiId, areaIndex, problemIndex, causeIndex, actionIndex) {
            const action = document.getElementById(`action-${kpiId}-${areaIndex}-${problemIndex}-${causeIndex}-${actionIndex}`);
            if (action) action.remove();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('kpi-batch-container');
            if (!container) return;

            // Initialize units/targets and actual mode.
            container.querySelectorAll('[data-kpi-id]').forEach((card) => {
                applyActualMode(card);
            });
            loadYearlyTargetsForAllCards();
        });

        // Re-load yearly targets when date changes.
        document.getElementById('entry_date')?.addEventListener('change', () => {
            loadYearlyTargetsForAllCards();
        });

        // Mark target as manually edited so we don't overwrite it.
        document.addEventListener('input', function(e) {
            const target = e.target;
            if (target && target.matches('[data-target-input]')) {
                target.dataset.autofilled = '0';
            }
        });

        // Aggregated actual recompute on dynamic field input.
        document.addEventListener('input', function(e) {
            const el = e.target;
            if (!el || !el.matches('[data-dynamic-field]')) return;
            const card = el.closest('[data-kpi-id]');
            if (!card) return;
            computeAggregatedActual(card);
        });

        // CAPA button handlers
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-add-area],[data-remove-area],[data-add-problem],[data-remove-problem],[data-add-cause],[data-remove-cause],[data-add-action],[data-remove-action]');
            if (!btn) return;

            if (btn.matches('[data-add-area]')) {
                const card = btn.closest('[data-kpi-id]');
                addCapaArea(card.dataset.kpiId);
                return;
            }

            if (btn.matches('[data-remove-area]')) {
                removeCapaArea(btn.dataset.kpiId, btn.dataset.areaIndex);
                return;
            }

            if (btn.matches('[data-add-problem]')) {
                addProblem(btn.dataset.kpiId, btn.dataset.areaIndex);
                return;
            }

            if (btn.matches('[data-remove-problem]')) {
                removeProblem(btn.dataset.kpiId, btn.dataset.areaIndex, btn.dataset.problemIndex);
                return;
            }

            if (btn.matches('[data-add-cause]')) {
                addCause(btn.dataset.kpiId, btn.dataset.areaIndex, btn.dataset.problemIndex);
                return;
            }

            if (btn.matches('[data-remove-cause]')) {
                removeCause(btn.dataset.kpiId, btn.dataset.areaIndex, btn.dataset.problemIndex, btn.dataset.causeIndex);
                return;
            }

            if (btn.matches('[data-add-action]')) {
                addActionPlan(btn.dataset.kpiId, btn.dataset.areaIndex, btn.dataset.problemIndex, btn.dataset.causeIndex);
                return;
            }

            if (btn.matches('[data-remove-action]')) {
                removeAction(btn.dataset.kpiId, btn.dataset.areaIndex, btn.dataset.problemIndex, btn.dataset.causeIndex, btn.dataset.actionIndex);
                return;
            }
        });

        // CAPA area select handling (dynamic areas)
        document.addEventListener('change', function(e) {
            const select = e.target;
            if (!select || !select.matches('[data-area-select]')) return;
            toggleCustomAreaInput(select.dataset.kpiId, select.dataset.areaIndex, select);
        });

        // Ensure selected area name is written into hidden input before submit.
        document.getElementById('kpiEntryForm')?.addEventListener('submit', function() {
            document.querySelectorAll('[data-area-select]').forEach((select) => {
                const kpiId = select.dataset.kpiId;
                const areaIndex = select.dataset.areaIndex;
                const input = document.getElementById(`capa_area_name_${kpiId}_${areaIndex}`);
                if (!input) return;
                if (select.value && select.value !== '__custom__') {
                    input.value = select.value;
                }
            });
        });
    </script>
</x-app-layout>
