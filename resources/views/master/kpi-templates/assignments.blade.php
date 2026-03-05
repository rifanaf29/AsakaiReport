<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">KPI Template Assignments</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Assign templates to a department and set the department-specific title.</p>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-100 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
            <div class="font-semibold mb-1">Could not save KPIs</div>
            <ul class="list-disc pl-5 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <form method="GET" action="{{ route('master.kpi-template-assignments.index') }}">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                    <select name="department" class="form-select rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800" onchange="this.form.submit()">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $selectedDepartmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <form method="POST" action="{{ route('master.kpi-template-assignments.update') }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="department_id" value="{{ $selectedDepartmentId }}">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Department KPIs</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Each KPI is an instance that uses a template layout. You can use the same template multiple times with different KPI names.</p>
                    </div>
                    <button type="button" onclick="addKpiRow()" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-200">
                        + Add KPI
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-auto w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20">
                            <tr>
                                <th class="px-4 py-3 text-left">KPI Name</th>
                                <th class="px-4 py-3 text-left">Template</th>
                                <th class="px-4 py-3 text-center">Active</th>
                                <th class="px-4 py-3 text-center">Sort</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="kpi-rows" class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($kpis as $idx => $kpi)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="kpis[{{ $idx }}][id]" value="{{ $kpi->id }}">
                                        <input type="text"
                                               name="kpis[{{ $idx }}][display_name]"
                                               value="{{ old('kpis.' . $idx . '.display_name', $kpi->display_name) }}"
                                               class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                               placeholder="e.g., Scrap Rate">
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="kpis[{{ $idx }}][kpi_template_id]" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                            @foreach($templates as $template)
                                                <option value="{{ $template->id }}" {{ (string) old('kpis.' . $idx . '.kpi_template_id', $kpi->kpi_template_id) === (string) $template->id ? 'selected' : '' }}>
                                                    {{ $template->code }} ({{ $template->fields_count ?? 0 }} fields)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="kpis[{{ $idx }}][is_active]" value="1" class="form-checkbox rounded border-gray-300 dark:border-gray-700" {{ old('kpis.' . $idx . '.is_active', $kpi->is_active) ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" min="0" step="1" name="kpis[{{ $idx }}][sort_order]" value="{{ old('kpis.' . $idx . '.sort_order', $kpi->sort_order) }}"
                                               class="form-input w-24 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" onclick="removeKpiRow(this)" class="text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="kpi-empty">
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No KPIs yet. Click “Add KPI”.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Save KPIs
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let kpiRowIndex = {{ (int) ($kpis->count() ?? 0) }};

        function removeKpiRow(button) {
            const row = button.closest('tr');
            if (row) row.remove();
        }

        function addKpiRow() {
            const tbody = document.getElementById('kpi-rows');
            const empty = document.getElementById('kpi-empty');
            if (empty) empty.remove();

            const idx = kpiRowIndex++;
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="text"
                           name="kpis[${idx}][display_name]"
                           value=""
                           class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                           placeholder="e.g., Scrap Rate">
                </td>
                <td class="px-4 py-3">
                    <select name="kpis[${idx}][kpi_template_id]" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        ${templateOptionsHtml()}
                    </select>
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="checkbox" name="kpis[${idx}][is_active]" value="1" class="form-checkbox rounded border-gray-300 dark:border-gray-700" checked>
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="number" min="0" step="1" name="kpis[${idx}][sort_order]" value="" class="form-input w-24 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </td>
                <td class="px-4 py-3 text-right">
                    <button type="button" onclick="removeKpiRow(this)" class="text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                </td>
            `;

            tbody.appendChild(tr);
        }

        function templateOptionsHtml() {
            const templates = @json($templates->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'fields' => $t->fields_count ?? 0]));
            return templates.map(t => `<option value="${t.id}">${t.code} (${t.fields} fields)</option>`).join('');
        }
    </script>
</x-app-layout>
