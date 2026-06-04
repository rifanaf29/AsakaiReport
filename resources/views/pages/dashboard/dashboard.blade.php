<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-8 gap-4">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Dashboard</h1>
                <div class="text-sm text-gray-500 dark:text-gray-400">Actual vs Target ({{ $selectedMonthLabel ?? 'Selected Month' }})</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">KPI: {{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}</div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end min-w-0">
                @if(!empty($selectedCapaStatus))
                    <input type="hidden" name="capa_status" value="{{ $selectedCapaStatus }}" />
                @endif
                <div class="sm:col-span-3 min-w-0">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                    <select id="dashboard-dept" name="department" class="form-select w-full min-w-0 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$selectedDepartmentId === (string)$dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3 min-w-0">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Month</label>
                    <input type="month" name="month" value="{{ $selectedMonth ?? '' }}" class="form-input w-full rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200" />
                </div>

                <div class="sm:col-span-3 min-w-0">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">KPI</label>
                    <select id="dashboard-kpi" name="kpi_definition_id" class="form-select w-full max-w-full min-w-0 truncate rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        @foreach($kpis as $kpi)
                            @php
                                $kpiName = $kpi->display_name ?: ($kpi->template?->code ?: 'KPI');
                            @endphp
                            <option value="{{ $kpi->id }}" {{ (string)($selectedKpiDefinitionId ?? '') === (string)$kpi->id ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Str::limit($kpiName, 60) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3 flex flex-wrap gap-2 justify-end">
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">Apply</button>
                    <a href="{{ route('dashboard') }}" class="btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Reset</a>
                    <a href="{{ route('dashboard', array_merge(request()->query(), ['fullscreen' => 1])) }}" class="btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Fullscreen</a>
                </div>
            </form>
            <script>
                document.getElementById('dashboard-dept').addEventListener('change', function () {
                    document.getElementById('dashboard-kpi').value = '';
                    this.closest('form').submit();
                });
            </script>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- KPI Actual vs Target Chart -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            <span class="inline-flex shrink-0 text-blue-600 dark:text-blue-400" aria-hidden="true">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </span>
                            <span>KPI Actual vs Target</span>
                        </h2>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $selectedMonthLabel ?? '' }}{{ ($selectedMonthLabel ?? null) ? ' • ' : '' }}{{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}
                        </div>
                    </div>
                </header>
                <div class="p-5">
                    <div id="kpi-chart-table-sync" class="overflow-x-auto">
                        <div id="kpi-chart-table-sync-inner">
                            <div class="kpi-chart-table-wrap shrink-0 overflow-hidden mb-2" style="height:280px;min-height:280px;max-height:280px;box-sizing:border-box;">
                                <canvas id="kpi-actual-target-chart" height="280" width="800"></canvas>
                            </div>
                    @include('pages.dashboard.partials.kpi-pivot-table')

                            </div>
                        </div>
                    </div>

                    @php
                        $kpiFieldUnitsForJs = $kpiChartMeta['field_units'] ?? [];
                        if ($kpiFieldUnitsForJs === [] && ! empty($selectedKpiDefinition?->template)) {
                            $kpiFieldUnitsForJs = $selectedKpiDefinition->template->fields()->pluck('unit', 'field_key')->all();
                        }
                        $kpiChartMetaJs = array_merge($kpiChartMeta ?? [], [
                            'month_label' => $selectedMonthLabel ?? null,
                            'field_units' => $kpiFieldUnitsForJs,
                        ]);
                    @endphp
                    <script>
                        window.kpiActualTargetChartData = @json($kpiChartData);
                        window.kpiActualTargetChartMeta = @json($kpiChartMetaJs);
                        window.kpiSeriesLabels = @json($kpiSeriesLabels ?? []);
                    </script>
                </div>
            </div>

            <!-- CAPA Problems Table -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex flex-col items-center gap-3 text-center">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-100">Recent CAPA Problems</h2>
                    <div id="dashboard-capa-status-summary" class="w-full flex justify-center">
                        @include('pages.dashboard.partials.capa-status-summary', [
                            'capaStatusCounts' => $capaStatusCounts ?? [],
                            'selectedCapaStatus' => $selectedCapaStatus ?? null,
                        ])
                    </div>
                </header>
                <script>
                    (function () {
                        const root = document.getElementById('dashboard-capa-status-summary');
                        if (!root) return;

                        root.addEventListener('click', (event) => {
                            const button = event.target.closest('.capa-status-filter');
                            if (!button) return;

                            const nextStatus = button.dataset.capaStatus || '';
                            const url = new URL(window.location.href);
                            const currentStatus = url.searchParams.get('capa_status') || '';

                            if (!nextStatus || currentStatus === nextStatus) {
                                url.searchParams.delete('capa_status');
                            } else {
                                url.searchParams.set('capa_status', nextStatus);
                            }

                            window.location.href = url.toString();
                        });
                    })();
                </script>
                <div class="p-3">
                    <div class="overflow-x-auto">
                        <table class="table-fixed w-full dark:text-gray-300">
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50 rounded-xs">
                                <tr>
                                    <th class="p-2 w-28 sticky left-0 z-20 bg-gray-50 dark:bg-gray-700/50"><div class="font-semibold text-left">Date</div></th>
                                    <th class="p-2 w-32 sticky left-28 z-20 bg-gray-50 dark:bg-gray-700/50"><div class="font-semibold text-left">Area</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Problem</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Root Cause</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Action Plan</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-left">Due Date</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-center">Status</div></th>
                                    <th class="p-2 w-40"><div class="font-semibold text-left">Notes</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-left">PIC</div></th>
                                    <th class="p-2 w-16"><div class="font-semibold text-center">Sev</div></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @php
                                    $capaGroups = collect($capaProblems)->groupBy(function ($problem) {
                                        $date = optional($problem->area)->capa_date ?? $problem->created_at;
                                        return $date ? $date->format('Y-m-d') : '-';
                                    })->sortKeysDesc();

                                    $rowsForCause = function ($cause) {
                                        $count = $cause?->actionPlans?->count() ?? 0;
                                        return max(1, $count);
                                    };
                                    $rowsForProblem = function ($problem) use ($rowsForCause) {
                                        $causes = $problem->causes ?? collect();
                                        if ($causes->isEmpty()) return 1;
                                        return (int) $causes->sum(fn ($c) => $rowsForCause($c));
                                    };
                                @endphp

                                @forelse($capaGroups as $dateKey => $problemsForDate)
                                    @php
                                        $areasForDate = $problemsForDate->groupBy(fn ($p) => optional($p->area)->id ?? 'no-area');
                                        $dateRowspan = (int) $areasForDate->sum(fn ($problemsInArea) => $problemsInArea->sum(fn ($p) => $rowsForProblem($p)));
                                        $rowStripeIndex = 0;
                                        $printedDate = false;
                                    @endphp

                                    @foreach($areasForDate as $areaId => $problemsInArea)
                                        @php
                                            $areaName = optional($problemsInArea->first()->area)->area_name ?? '-';
                                            $areaRowspan = (int) $problemsInArea->sum(fn ($p) => $rowsForProblem($p));
                                            $printedArea = false;
                                        @endphp

                                        @foreach($problemsInArea as $problem)
                                            @php
                                                $problemRowspan = (int) $rowsForProblem($problem);
                                                $printedProblem = false;
                                                $causes = ($problem->causes ?? collect())->sortBy('sort_order')->values();
                                                if ($causes->isEmpty()) {
                                                    $causes = collect([null]);
                                                }
                                            @endphp

                                            @foreach($causes as $cause)
                                                @php
                                                    $actions = $cause ? ($cause->actionPlans ?? collect())->sortBy('due_date')->values() : collect();
                                                    if ($actions->isEmpty()) {
                                                        $actions = collect([null]);
                                                    }
                                                    $causeRowspan = $cause ? max(1, (int) (($cause->actionPlans ?? collect())->count())) : 1;
                                                    $printedCause = false;
                                                @endphp

                                                @foreach($actions as $action)
                                                    @php
                                                        $rowStripeIndex++;
                                                        $rowClass = ($rowStripeIndex % 2 === 0)
                                                            ? 'bg-gray-50 dark:bg-gray-800/40'
                                                            : 'bg-white dark:bg-gray-800';

                                                        $actionStatus = $action?->status;
                                                        $actionStatusLabel = $actionStatus === 'close'
                                                            ? 'Closed'
                                                            : ($actionStatus === 'progress' ? 'In Progress' : ($actionStatus === 'open' ? 'Open' : '-'));
                                                    @endphp
                                                    <tr class="{{ $rowClass }} hover:bg-gray-100 dark:hover:bg-gray-700/30">
                                                        @if(!$printedDate)
                                                            <td class="p-2 align-top bg-gray-50 dark:bg-gray-700/20 sticky left-0 z-10" rowspan="{{ $dateRowspan }}">
                                                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $dateKey }}</div>
                                                            </td>
                                                            @php($printedDate = true)
                                                        @endif

                                                        @if(!$printedArea)
                                                            <td class="p-2 align-top bg-gray-50 dark:bg-gray-700/10 sticky left-28 z-10" rowspan="{{ $areaRowspan }}">
                                                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $areaName }}</div>
                                                            </td>
                                                            @php($printedArea = true)
                                                        @endif

                                                        @if(!$printedProblem)
                                                            <td class="p-2 align-top" rowspan="{{ $problemRowspan }}">
                                                                <a href="{{ route('capa.problems.show', $problem) }}" class="text-gray-800 dark:text-gray-100 hover:underline line-clamp-3">
                                                                    {{ $problem->problem_description }}
                                                                </a>
                                                            </td>
                                                            @php($printedProblem = true)
                                                        @endif

                                                        @if(!$printedCause)
                                                            <td class="p-2 align-top" rowspan="{{ $causeRowspan }}">
                                                                <div class="text-gray-800 dark:text-gray-100 line-clamp-3">{{ $cause?->cause_description ?: '-' }}</div>
                                                            </td>
                                                            @php($printedCause = true)
                                                        @endif

                                                        <td class="p-2">
                                                            <div class="text-gray-800 dark:text-gray-100 line-clamp-3">{{ $action?->description ?: '-' }}</div>
                                                        </td>
                                                        <td class="p-2">
                                                            <div class="text-gray-800 dark:text-gray-100">{{ $action?->due_date ? $action->due_date->format('Y-m-d') : '-' }}</div>
                                                        </td>
                                                        <td class="p-2">
                                                            <div class="text-center">
                                                                <span class="px-2 py-0.5 rounded-full text-xs
                                                                    {{ $actionStatusLabel === 'Closed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($actionStatusLabel === 'In Progress' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : ($actionStatusLabel === 'Open' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200')) }}">
                                                                    {{ $actionStatusLabel }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td class="p-2">
                                                            @php($actionNotes = $action?->keterangan ?? $action?->completion_notes)
                                                            <div class="text-gray-800 dark:text-gray-100 line-clamp-3">{{ $actionNotes ?: '-' }}</div>
                                                        </td>
                                                        <td class="p-2">
                                                            @php($picName = $action ? ($action->person_in_charge ?? optional($action->pic)->name) : null)
                                                            <div class="text-gray-800 dark:text-gray-100">{{ $picName ?: '-' }}</div>
                                                        </td>
                                                        <td class="p-2">
                                                            @php($severityKey = strtolower((string) ($problem->severity ?? '')))
                                                            @php($severityLabel = $severityKey === 'high' ? 'H' : ($severityKey === 'medium' ? 'M' : ($severityKey === 'low' ? 'L' : ($severityKey ? strtoupper(substr($severityKey, 0, 1)) : '-'))))
                                                            <div class="text-center">
                                                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                                    {{ $severityLabel }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @empty
                                    <tr>
                                        <td class="p-6 text-center text-gray-500 dark:text-gray-400" colspan="10">No CAPA problems found for the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
