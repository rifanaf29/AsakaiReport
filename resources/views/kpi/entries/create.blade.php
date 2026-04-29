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

            @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-5 py-4 rounded-xl shadow-sm">
                {{ session('success') }}
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

                        <input type="hidden" name="submit_kpi_definition_id" id="submit_kpi_definition_id" value="">

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

                        @if(($selectedDepartmentCode ?? null) === 'MN')
                        <div class="mb-6" id="mn-prefill-panel">
                            <label for="mn_working_hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Working Time (Hour) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="mn_working_hours" name="mn_working_hours"
                                   value="{{ old('mn_working_hours', '') }}"
                                   placeholder="e.g. 24"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="mn-prefill-status"></div>
                        </div>
                        @endif

                        @if($kpis->isEmpty())
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                {{ auth()->user()->can_access_all_departments && empty($selectedDepartmentId) ? 'Select a department first to load KPIs.' : 'No KPIs set for this department.' }}
                            </div>
                        @else
                            <div class="space-y-6" id="kpi-batch-container"
                                   data-department-id="{{ (int) ($selectedDepartmentId ?? 0) }}"
                                   data-department-code="{{ $selectedDepartmentCode ?? '' }}">
                                @foreach($kpis as $kpi)
                                    @php
                                        $kpiId = (int) $kpi->id;
                                        $tpl = $kpi->template;
                                        $kpiLabel = $kpi->display_name ?: ($tpl?->code ?? ('KPI #' . $kpiId));
                                        $showMonthlyTotal = $tpl && $tpl->code && \Illuminate\Support\Str::startsWith($tpl->code, 'TPL_HR_WASTE_');
                                    @endphp

                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm bg-white dark:bg-gray-800"
                                         data-kpi-id="{{ $kpiId }}"
                                         data-kpi-label="{{ $kpiLabel }}"
                                         data-actual-mode="{{ $tpl?->actual_mode ?? 'manual' }}"
                                         data-actual-aggregation="{{ $tpl?->actual_aggregation ?? '' }}"
                                         data-actual-field-keys='@json($tpl?->actual_field_keys ?? [])'
                                         data-actual-formula="{{ $tpl?->actual_formula ?? '' }}">
                                        <div class="flex items-start justify-between gap-4 mb-4">
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">KPI</div>
                                                <div class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $kpiLabel }}</div>
                                                @if($tpl)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Template: {{ $tpl->code }}</div>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <div class="text-xs text-gray-500 dark:text-gray-400" data-target-status></div>
                                                <button type="button" data-save-single
                                                        class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors">
                                                    Save this KPI
                                                </button>
                                            </div>
                                        </div>

                                        <div class="hidden mb-4 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3"
                                             data-existing-entry>
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="text-sm text-amber-900 dark:text-amber-200">
                                                    <div class="font-semibold">Already submitted for this date</div>
                                                    <div class="mt-1 text-xs text-amber-800 dark:text-amber-300" data-existing-entry-text></div>
                                                </div>
                                                <a href="#" class="text-xs underline text-amber-900 dark:text-amber-200 shrink-0" data-existing-entry-link target="_blank" rel="noopener noreferrer">View/Edit</a>
                                            </div>
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
                                                            $unitOverride = is_array($kpi->field_units ?? null) ? ($kpi->field_units[$fieldKey] ?? null) : null;
                                                            $fieldUnit = $unitOverride ?: ($field->unit ?? null);
                                                        @endphp
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                                {{ $field->field_name }}
                                                                @if($fieldUnit)
                                                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $fieldUnit }})</span>
                                                                @endif
                                                                @if($field->is_required) <span class="text-red-500">*</span>@endif
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
                                                                 @case('accounting')
                                                                    <div class="flex items-start gap-3">
                                                                        <input type="number" step="0.01"
                                                                               name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                                                                               value="{{ $oldVal }}"
                                                                               @if($field->is_required) required @endif
                                                                               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                                               data-dynamic-field data-field-key="{{ $fieldKey }}">

                                                                        @if($showMonthlyTotal)
                                                                            <div class="w-36">
                                                                                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Total</div>
                                                                                <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 text-right"
                                                                                     data-akumulasi
                                                                                     data-kpi-id="{{ $kpiId }}"
                                                                                     data-field-key="{{ $fieldKey }}">-</div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
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
                                                <p class="text-sm mt-1 text-gray-600 dark:text-gray-400" data-capa-hint>Fill if issues are identified or when status is NG</p>
                                            </div>

                                            <div class="mb-4">
                                                <button type="button" data-add-area
                                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors shadow-sm">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Add CAPA Area
                                                </button>
                                                <span class="ml-2 text-xs" data-capa-status-label></span>
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

        // Single KPI save (keeps the batch flow, but submits only one KPI).
        const kpiEntryForm = document.getElementById('kpiEntryForm');
        const singleSubmitInput = document.getElementById('submit_kpi_definition_id');
        if (singleSubmitInput) singleSubmitInput.value = '';

        document.querySelectorAll('[data-save-single]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('[data-kpi-id]');
                const kpiId = card?.getAttribute('data-kpi-id');
                if (!kpiId || !kpiEntryForm || !singleSubmitInput) return;

                singleSubmitInput.value = kpiId;
                btn.disabled = true;
                btn.textContent = 'Saving...';
                kpiEntryForm.submit();
            });
        });

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

        function computeCardStatus(card) {
            const targetInput = card.querySelector('[data-target-input]');
            const actualInput = card.querySelector('[data-actual-input]');
            if (!targetInput || !actualInput) return null;
            const target = parseFloat(targetInput.value);
            const actual = parseFloat(actualInput.value);
            if (!Number.isFinite(target) || !Number.isFinite(actual)) return null;
            const operator = card.dataset.targetOperator || 'gte';
            return operator === 'lte' ? (actual <= target ? 'OK' : 'NG') : (actual >= target ? 'OK' : 'NG');
        }

        function updateCapaRequirement(card) {
            const status = computeCardStatus(card);
            const isNg = status === 'NG';

            const hint = card.querySelector('[data-capa-hint]');
            const statusLabel = card.querySelector('[data-capa-status-label]');

            if (hint) {
                if (isNg) {
                    hint.textContent = 'Status is NG — CAPA is required.';
                    hint.className = 'text-sm mt-1 font-medium text-red-600 dark:text-red-400';
                } else {
                    hint.textContent = 'Fill if issues are identified or when status is NG';
                    hint.className = 'text-sm mt-1 text-gray-600 dark:text-gray-400';
                }
            }

            if (statusLabel) {
                if (isNg) {
                    statusLabel.textContent = 'At least one CAPA area is required';
                    statusLabel.className = 'ml-2 text-xs font-medium text-red-600 dark:text-red-400';
                } else {
                    statusLabel.textContent = 'CAPA is optional for this status';
                    statusLabel.className = 'ml-2 text-xs text-gray-500 dark:text-gray-400';
                }
            }

            card.dataset.capaRequired = isNg ? '1' : '0';
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

            if (aggregation === 'formula') {
                const formulaRaw = (card.dataset.actualFormula || '').trim();
                let expr = formulaRaw;
                if (expr.startsWith('=')) expr = expr.slice(1).trim();

                if (!expr) {
                    actualInput.value = '';
                    return;
                }

                const dynamicMap = {};
                card.querySelectorAll(`[name^="entries[${kpiId}][dynamic_fields]"]`).forEach((input) => {
                    const name = input.getAttribute('name') || '';
                    const match = name.match(/\[dynamic_fields\]\[([^\]]+)\]/);
                    if (!match) return;
                    const key = (match[1] || '').toLowerCase();
                    const num = parseFloat(input.value);
                    dynamicMap[key] = Number.isFinite(num) ? num : null;
                });

                const result = evaluateArithmeticExpression(expr, dynamicMap);
                actualInput.value = Number.isFinite(result) ? result.toFixed(2) : '';
                return;
            }

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

        function evaluateArithmeticExpression(expression, variables) {
            const tokens = tokenizeExpression(expression);
            if (!tokens) return NaN;
            const rpn = toRpn(tokens);
            if (!rpn) return NaN;
            return evalRpn(rpn, variables);
        }

        function tokenizeExpression(expression) {
            const tokens = [];
            const s = String(expression);
            let i = 0;
            let prevType = null; // number|ident|op|lparen|rparen

            while (i < s.length) {
                const ch = s[i];
                if (ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r') {
                    i++;
                    continue;
                }

                if (ch === '(') {
                    tokens.push({ type: 'lparen' });
                    prevType = 'lparen';
                    i++;
                    continue;
                }
                if (ch === ')') {
                    tokens.push({ type: 'rparen' });
                    prevType = 'rparen';
                    i++;
                    continue;
                }

                if (ch === '+' || ch === '-' || ch === '*' || ch === '/') {
                    const isUnary = (prevType === null || prevType === 'op' || prevType === 'lparen');
                    if (isUnary && ch === '+') {
                        i++;
                        continue;
                    }
                    const op = (isUnary && ch === '-') ? 'u-' : ch;
                    tokens.push({ type: 'op', value: op });
                    prevType = 'op';
                    i++;
                    continue;
                }

                // Number
                if ((ch >= '0' && ch <= '9') || ch === '.') {
                    let start = i;
                    let dotCount = 0;
                    while (i < s.length) {
                        const c = s[i];
                        if (c === '.') {
                            dotCount++;
                            if (dotCount > 1) break;
                            i++;
                            continue;
                        }
                        if (!(c >= '0' && c <= '9')) break;
                        i++;
                    }
                    const raw = s.slice(start, i);
                    const num = parseFloat(raw);
                    if (!Number.isFinite(num)) return null;
                    tokens.push({ type: 'number', value: num });
                    prevType = 'number';
                    continue;
                }

                // Identifier
                const isAlpha = (ch >= 'A' && ch <= 'Z') || (ch >= 'a' && ch <= 'z') || ch === '_';
                if (isAlpha) {
                    let start = i;
                    i++;
                    while (i < s.length) {
                        const c = s[i];
                        const isAlnum = (c >= '0' && c <= '9') || (c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z') || c === '_';
                        if (!isAlnum) break;
                        i++;
                    }
                    const ident = s.slice(start, i);
                    tokens.push({ type: 'ident', value: ident });
                    prevType = 'ident';
                    continue;
                }

                return null;
            }

            return tokens;
        }

        function toRpn(tokens) {
            const precedence = { 'u-': 3, '*': 2, '/': 2, '+': 1, '-': 1 };
            const rightAssoc = { 'u-': true };
            const output = [];
            const ops = [];

            for (const t of tokens) {
                if (t.type === 'number' || t.type === 'ident') {
                    output.push(t);
                    continue;
                }
                if (t.type === 'op') {
                    const op1 = t.value;
                    if (!(op1 in precedence)) return null;
                    while (ops.length) {
                        const top = ops[ops.length - 1];
                        if (top.type !== 'op') break;
                        const op2 = top.value;
                        if (!(op2 in precedence)) break;
                        const p1 = precedence[op1];
                        const p2 = precedence[op2];
                        const isRight = !!rightAssoc[op1];
                        if ((!isRight && p1 <= p2) || (isRight && p1 < p2)) {
                            output.push(ops.pop());
                            continue;
                        }
                        break;
                    }
                    ops.push(t);
                    continue;
                }
                if (t.type === 'lparen') {
                    ops.push(t);
                    continue;
                }
                if (t.type === 'rparen') {
                    let found = false;
                    while (ops.length) {
                        const top = ops.pop();
                        if (top.type === 'lparen') {
                            found = true;
                            break;
                        }
                        output.push(top);
                    }
                    if (!found) return null;
                    continue;
                }
                return null;
            }

            while (ops.length) {
                const top = ops.pop();
                if (top.type === 'lparen' || top.type === 'rparen') return null;
                output.push(top);
            }
            return output;
        }

        function evalRpn(rpn, variables) {
            const stack = [];
            for (const t of rpn) {
                if (t.type === 'number') {
                    stack.push(t.value);
                    continue;
                }
                if (t.type === 'ident') {
                    const key = String(t.value).toLowerCase();
                    const val = variables[key];
                    if (!Number.isFinite(val)) return NaN;
                    stack.push(val);
                    continue;
                }
                if (t.type === 'op') {
                    const op = t.value;
                    if (op === 'u-') {
                        if (stack.length < 1) return NaN;
                        stack.push(-stack.pop());
                        continue;
                    }
                    if (stack.length < 2) return NaN;
                    const b = stack.pop();
                    const a = stack.pop();
                    switch (op) {
                        case '+': stack.push(a + b); break;
                        case '-': stack.push(a - b); break;
                        case '*': stack.push(a * b); break;
                        case '/':
                            if (b === 0) return NaN;
                            stack.push(a / b);
                            break;
                        default:
                            return NaN;
                    }
                    continue;
                }
                return NaN;
            }

            return stack.length === 1 ? stack[0] : NaN;
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

                card.dataset.targetOperator = data?.target_operator || 'gte';

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

                updateCapaRequirement(card);
            } catch (error) {
                setTargetStatus(card, '(failed to load target)', 'error');
            }
        }

        function formatAkumulasiNumber(value) {
            const num = Number(value);
            if (!Number.isFinite(num)) return '-';
            const fixed = num.toFixed(2);
            // Trim trailing zeros (e.g. 10.00 -> 10, 10.50 -> 10.5)
            return fixed.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
        }

        function escapeHtml(value) {
            const s = String(value ?? '');
            return s
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setCardExistingEntry(card, entry) {
            const panel = card.querySelector('[data-existing-entry]');
            const textEl = card.querySelector('[data-existing-entry-text]');
            const linkEl = card.querySelector('[data-existing-entry-link]');

            const getOrCreatePlaceholder = () => {
                const existing = card.previousElementSibling;
                if (existing && existing.matches('[data-existing-placeholder]')) {
                    return existing;
                }

                const el = document.createElement('div');
                el.setAttribute('data-existing-placeholder', '');
                el.className = 'rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-5 py-4';
                card.parentNode?.insertBefore(el, card);
                return el;
            };

            const removePlaceholder = () => {
                const prev = card.previousElementSibling;
                if (prev && prev.matches('[data-existing-placeholder]')) {
                    prev.remove();
                }
            };

            const targetInput = card.querySelector('[data-target-input]');
            const actualInput = card.querySelector('[data-actual-input]');
            const kpiId = card.dataset.kpiId;
            const notesInput = kpiId ? card.querySelector(`textarea[name="entries[${kpiId}][notes]"]`) : null;

            const controls = card.querySelectorAll('input, textarea, select, button');

            const stashValue = (el, key) => {
                if (!el) return;
                const prevKey = `prev${key}`;
                if (el.dataset[prevKey] === undefined) {
                    el.dataset[prevKey] = el.value ?? '';
                }
            };
            const restoreValue = (el, key) => {
                if (!el) return;
                const prevKey = `prev${key}`;
                if (el.dataset[prevKey] !== undefined) {
                    el.value = el.dataset[prevKey];
                    delete el.dataset[prevKey];
                }
            };

            if (!entry) {
                removePlaceholder();
                card.classList.remove('hidden');

                if (panel) panel.classList.add('hidden');
                if (textEl) textEl.textContent = '';
                if (linkEl) linkEl.setAttribute('href', '#');

                restoreValue(targetInput, 'Target');
                restoreValue(actualInput, 'Actual');
                restoreValue(notesInput, 'Notes');
                if (targetInput && targetInput.dataset.prevAutofilled !== undefined) {
                    targetInput.dataset.autofilled = targetInput.dataset.prevAutofilled;
                    delete targetInput.dataset.prevAutofilled;
                }

                controls.forEach((el) => {
                    el.disabled = false;
                });

                // Re-apply aggregated actual state if needed.
                applyActualMode(card);
                return;
            }

            // Hide the full card to reduce confusion, and show a compact placeholder instead.
            const placeholder = getOrCreatePlaceholder();
            const label = card.dataset.kpiLabel || card.querySelector('.text-lg')?.textContent?.trim() || `KPI #${card.dataset.kpiId}`;
            const href = entry.edit_url || entry.show_url || '#';
            placeholder.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="text-sm text-amber-900 dark:text-amber-200">
                        <div class="font-semibold">${escapeHtml(label)} already submitted</div>
                        <div class="mt-1 text-xs text-amber-800 dark:text-amber-300">Target: ${escapeHtml(String(entry.target ?? ''))} • Actual: ${escapeHtml(String(entry.actual ?? ''))} • Status: ${escapeHtml(String(entry.status ?? ''))}</div>
                    </div>
                    <a href="${escapeHtml(href)}" class="text-xs underline text-amber-900 dark:text-amber-200 shrink-0" target="_blank" rel="noopener noreferrer">View/Edit</a>
                </div>
            `;

            card.classList.add('hidden');

            stashValue(targetInput, 'Target');
            stashValue(actualInput, 'Actual');
            stashValue(notesInput, 'Notes');
            if (targetInput && targetInput.dataset.prevAutofilled === undefined) {
                targetInput.dataset.prevAutofilled = targetInput.dataset.autofilled ?? '';
            }

            if (targetInput && entry.target !== undefined && entry.target !== null) {
                targetInput.value = entry.target;
                targetInput.dataset.autofilled = '0';
            }
            if (actualInput && entry.actual !== undefined && entry.actual !== null) {
                actualInput.value = entry.actual;
            }
            if (notesInput && entry.notes) {
                notesInput.value = entry.notes;
            }

            if (panel) panel.classList.remove('hidden');
            if (textEl) {
                const target = entry.target ?? '';
                const actual = entry.actual ?? '';
                const status = entry.status ?? '';
                textEl.textContent = `Target: ${target} • Actual: ${actual} • Status: ${status}`;
            }
            if (linkEl) {
                linkEl.setAttribute('href', entry.edit_url || entry.show_url || '#');
            }

            controls.forEach((el) => {
                el.disabled = true;
            });
        }

        async function loadExistingEntriesForAllCards() {
            const container = document.getElementById('kpi-batch-container');
            if (!container) return;

            const entryDate = document.getElementById('entry_date')?.value;
            const departmentId = getDepartmentId();
            if (!entryDate || !departmentId) return;

            const cards = Array.from(container.querySelectorAll('[data-kpi-id]'));
            const kpiIds = cards
                .map((card) => parseInt(card.dataset.kpiId, 10))
                .filter((id) => Number.isFinite(id) && id > 0);

            if (!kpiIds.length) return;

            try {
                const url = new URL(`{{ route('kpi.entries.existing') }}`, window.location.origin);
                url.searchParams.set('date', entryDate);
                url.searchParams.set('department_id', departmentId);
                kpiIds.forEach((id) => url.searchParams.append('kpi_definition_ids[]', String(id)));

                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const json = await res.json();
                const data = json?.data || {};
                const computed = json?.computed || {};
                const source = json?.source || {};

                cards.forEach((card) => {
                    const id = parseInt(card.dataset.kpiId, 10);
                    setCardExistingEntry(card, data[id] || null);
                });
            } catch (e) {
                // ignore
            }
        }

        async function loadAkumulasiForAllCards() {
            const container = document.getElementById('kpi-batch-container');
            if (!container) return;

            const entryDate = document.getElementById('entry_date')?.value;
            const departmentId = getDepartmentId();
            if (!entryDate || !departmentId) return;

            const kpiIds = Array.from(container.querySelectorAll('[data-kpi-id]'))
                .map((card) => parseInt(card.dataset.kpiId, 10))
                .filter((id) => Number.isFinite(id) && id > 0);

            if (!kpiIds.length) return;

            try {
                const url = new URL(`{{ route('kpi.entries.akumulasi') }}`, window.location.origin);
                url.searchParams.set('date', entryDate);
                url.searchParams.set('department_id', departmentId);
                url.searchParams.set('kpi_definition_ids', kpiIds.join(','));

                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                const json = await res.json();
                const data = json?.data || {};

                container.querySelectorAll('[data-akumulasi]').forEach((el) => {
                    const kpiId = parseInt(el.dataset.kpiId, 10);
                    const fieldKey = el.dataset.fieldKey;
                    const val = data?.[kpiId]?.[fieldKey];
                    el.textContent = formatAkumulasiNumber(val);
                });
            } catch (e) {
                // keep '-' on error
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
            loadExistingEntriesForAllCards();
            loadYearlyTargetsForAllCards();
            loadAkumulasiForAllCards();
            loadMnPrefillForAllCards();
        });

        // Re-load yearly targets when date changes.
        document.getElementById('entry_date')?.addEventListener('change', () => {
            loadExistingEntriesForAllCards();
            loadYearlyTargetsForAllCards();
            loadAkumulasiForAllCards();
            loadMnPrefillForAllCards();
        });

        document.getElementById('mn_working_hours')?.addEventListener('input', () => {
            loadMnPrefillForAllCards();
        });

        // Mark target as manually edited so we don't overwrite it.
        document.addEventListener('input', function(e) {
            const target = e.target;
            if (target && target.matches('[data-target-input]')) {
                target.dataset.autofilled = '0';
                const card = target.closest('[data-kpi-id]');
                if (card) updateCapaRequirement(card);
            }
        });

        // Mark actual as manually edited (MN prefill) so we don't overwrite it.
        document.addEventListener('input', function(e) {
            const el = e.target;
            if (el && el.matches('[data-actual-input]')) {
                el.dataset.prefilled = '0';
                const card = el.closest('[data-kpi-id]');
                if (card) updateCapaRequirement(card);
            }
        });

        function isMaintenanceDepartment() {
            const container = document.getElementById('kpi-batch-container');
            return container && (container.dataset.departmentCode || '').toUpperCase() === 'MN';
        }

        function setMnPrefillStatus(text, kind = 'muted') {
            const el = document.getElementById('mn-prefill-status');
            if (!el) return;
            el.textContent = text;
            if (kind === 'success') el.className = 'mt-2 text-xs text-green-600 dark:text-green-400';
            else if (kind === 'warning') el.className = 'mt-2 text-xs text-yellow-600 dark:text-yellow-400';
            else if (kind === 'error') el.className = 'mt-2 text-xs text-red-600 dark:text-red-400';
            else el.className = 'mt-2 text-xs text-gray-500 dark:text-gray-400';
        }

        async function loadMnPrefillForAllCards() {
            if (!isMaintenanceDepartment()) return;

            const container = document.getElementById('kpi-batch-container');
            if (!container) return;

            const entryDate = document.getElementById('entry_date')?.value;
            const departmentId = getDepartmentId();
            const workingHoursVal = document.getElementById('mn_working_hours')?.value;
            const workingHours = parseFloat(workingHoursVal);

            if (!entryDate || !departmentId) return;

            if (!Number.isFinite(workingHours) || workingHours <= 0) {
                setMnPrefillStatus('Enter working time to prefill actuals.', 'warning');
                return;
            }

            const cards = Array.from(container.querySelectorAll('[data-kpi-id]'));
            const kpiIds = cards
                .map((card) => parseInt(card.dataset.kpiId, 10))
                .filter((id) => Number.isFinite(id) && id > 0);

            if (!kpiIds.length) return;

            setMnPrefillStatus('(prefill loading...)', 'muted');

            try {
                const url = new URL(`{{ route('kpi.entries.mn-prefill') }}`, window.location.origin);
                url.searchParams.set('date', entryDate);
                url.searchParams.set('department_id', departmentId);
                url.searchParams.set('working_hours', String(workingHours));
                kpiIds.forEach((id) => url.searchParams.append('kpi_definition_ids[]', String(id)));

                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const contentType = (res.headers.get('content-type') || '').toLowerCase();

                if (!res.ok) {
                    setMnPrefillStatus('Prefill failed (API error).', 'error');
                    return;
                }

                if (!contentType.includes('application/json')) {
                    setMnPrefillStatus('Prefill failed (unexpected response).', 'error');
                    return;
                }

                let json;
                try {
                    json = await res.json();
                } catch (err) {
                    setMnPrefillStatus('Prefill failed (invalid JSON).', 'error');
                    return;
                }

                const data = json?.data || {};
                const computed = json?.computed || {};
                const source = json?.source || {};

                cards.forEach((card) => {
                    const id = parseInt(card.dataset.kpiId, 10);
                    const actualVal = data?.[id]?.actual;
                    if (actualVal === null || actualVal === undefined) return;

                    const actualInput = card.querySelector('[data-actual-input]');
                    if (!actualInput) return;

                    const shouldAutofill = (!actualInput.value || actualInput.dataset.prefilled === '1');
                    if (!shouldAutofill) return;

                    const num = parseFloat(actualVal);
                    actualInput.value = Number.isFinite(num) ? num.toFixed(2) : '';
                    actualInput.dataset.prefilled = '1';
                });

                const dt = Number.isFinite(parseFloat(computed?.downtime_pct)) ? parseFloat(computed.downtime_pct).toFixed(2) : null;
                const mttr = Number.isFinite(parseFloat(computed?.mttr)) ? parseFloat(computed.mttr).toFixed(2) : null;
                const mtbf = Number.isFinite(parseFloat(computed?.mtbf)) ? parseFloat(computed.mtbf).toFixed(2) : null;

                const wh = Number.isFinite(parseFloat(source?.working_hours)) ? parseFloat(source.working_hours).toFixed(2) : null;
                const dtH = Number.isFinite(parseFloat(source?.total_downtime_hour)) ? parseFloat(source.total_downtime_hour).toFixed(2) : null;
                const fin = Number.isFinite(parseFloat(source?.total_tickets)) ? parseFloat(source.total_tickets).toFixed(0) : null;

                const details = [
                    (wh !== null || dtH !== null || fin !== null)
                        ? `WH=${wh ?? '-'}h | DT_H=${dtH ?? '-'}h | FIN=${fin ?? '-'}`
                        : null,
                    dt !== null ? `DT=${dt}%` : null,
                    mttr !== null ? `MTTR=${mttr}` : null,
                    mtbf !== null ? `MTBF=${mtbf}` : null,
                ].filter(Boolean).join(' | ');

                const used = source?.payload_keys_used;
                const usedMsg = used ? ` (keys: downtime=${used.downtime ?? '-'}, tickets=${used.tickets ?? '-'})` : '';

                setMnPrefillStatus(details ? `Prefill applied. ${details}${usedMsg}` : `Prefill applied.${usedMsg}`, 'success');
            } catch (e) {
                setMnPrefillStatus('Prefill failed (network).', 'error');
            }
        }

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
        document.getElementById('kpiEntryForm')?.addEventListener('submit', function(e) {
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
