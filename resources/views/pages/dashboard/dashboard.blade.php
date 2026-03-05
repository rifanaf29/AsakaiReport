<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Dashboard</h1>
                <div class="text-sm text-gray-500 dark:text-gray-400">Actual vs Target ({{ $selectedMonthLabel ?? 'Selected Month' }})</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">KPI: {{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}</div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                    <select name="department" class="form-select w-full rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$selectedDepartmentId === (string)$dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Month</label>
                    <input type="month" name="month" value="{{ $selectedMonth ?? '' }}" class="form-input w-full rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">KPI</label>
                    <select name="kpi_definition_id" class="form-select w-full rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <option value="">All KPIs</option>
                        @foreach($kpis as $kpi)
                            @php
                                $kpiName = $kpi->display_name ?: ($kpi->template?->code ?: 'KPI');
                                $tplCode = $kpi->template?->code;
                            @endphp
                            <option value="{{ $kpi->id }}" {{ (string)($selectedKpiDefinitionId ?? '') === (string)$kpi->id ? 'selected' : '' }}>
                                {{ $kpiName }}{{ $tplCode ? ' ('.$tplCode.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">Apply</button>
                    <a href="{{ route('dashboard') }}" class="btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Reset</a>
                    <a href="{{ route('dashboard', array_merge(request()->query(), ['fullscreen' => 1])) }}" class="btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Fullscreen</a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- KPI Actual vs Target Chart -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100">KPI Actual vs Target</h2>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $selectedMonthLabel ?? '' }}{{ ($selectedMonthLabel ?? null) ? ' • ' : '' }}{{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}
                        </div>
                    </div>
                </header>
                <div class="p-5">
                    <div class="h-[320px]">
                        <canvas id="kpi-actual-target-chart" height="320"></canvas>
                    </div>

                    @php
                        $kpiTableLabels = collect($kpiChartData['labels'] ?? []);
                        $kpiTableSeries = collect($kpiChartData)
                            ->except('labels')
                            ->filter(fn ($v) => is_iterable($v))
                            ->map(fn ($v) => collect($v));

                        $targetSeries = $kpiTableSeries->get('target', collect());
                        $actualSeries = $kpiTableSeries->get('actual', collect());

                        $operator = $kpiChartMeta['target_operator'] ?? 'gte';

                        $statusSeries = $kpiTableLabels->values()->map(function ($_, $index) use ($targetSeries, $actualSeries, $operator) {
                            $target = $targetSeries->get($index);
                            $actual = $actualSeries->get($index);

                            if ($target === null || $target === '' || $actual === null || $actual === '') return null;
                            if (!is_numeric($target) || !is_numeric($actual)) return null;

                            if ($operator === 'lte') {
                                return ((float) $actual <= (float) $target) ? 'OK' : 'NG';
                            }

                            return ((float) $actual >= (float) $target) ? 'OK' : 'NG';
                        });

                        $kpiTableSeries = $kpiTableSeries->merge([
                            'status' => $statusSeries,
                        ]);

                        $kpiUnit = $kpiChartMeta['unit'] ?? null;
                        $formatKpiCell = function ($value) {
                            if ($value === null || $value === '') return '';
                            if (!is_numeric($value)) return '';
                            $number = (float) $value;
                            return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
                        };

                        $actualNumeric = $actualSeries->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
                        $actualSum = $actualNumeric->sum();
                        $actualCount = $actualNumeric->count();
                        $actualAvg = $actualCount ? ($actualSum / $actualCount) : null;

                        $targetValue = $targetSeries->first(fn ($v) => is_numeric($v));

                        $okCount = $statusSeries->filter(fn ($v) => $v === 'OK')->count();
                        $ngCount = $statusSeries->filter(fn ($v) => $v === 'NG')->count();
                    @endphp

                    <div class="mt-4 overflow-x-auto">
                        <table class="table-auto w-full text-xs dark:text-gray-300">
                            <thead class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-2"><div class="font-semibold text-left">Field</div></th>
                                    @foreach($kpiTableLabels as $label)
                                        @php
                                            try {
                                                $labelDate = \Carbon\Carbon::createFromFormat('m-d-Y', $label);
                                                $labelText = $labelDate->format('d');
                                                $dowIso = $labelDate->dayOfWeekIso; // 1=Mon ... 6=Sat, 7=Sun
                                                $dateHeaderCellStyle = $dowIso === 6
                                                    ? 'background-color: rgb(255, 255, 0);'
                                                    : ($dowIso === 7 ? 'background-color: rgb(192, 0, 0);' : '');
                                                $dateHeaderTextClass = $dateHeaderCellStyle ? 'text-white' : '';
                                            } catch (\Exception $e) {
                                                $labelText = $label;
                                                $dateHeaderCellStyle = '';
                                                $dateHeaderTextClass = '';
                                            }
                                        @endphp
                                        <th class="p-2 whitespace-nowrap" style="{{ $dateHeaderCellStyle }}"><div class="font-semibold text-center {{ $dateHeaderTextClass }}">{{ $labelText }}</div></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @foreach($kpiTableSeries as $seriesName => $seriesValues)
                                    <tr>
                                        <td class="p-2">
                                            @php
                                                $seriesLabel = $kpiSeriesLabels[$seriesName] ?? \Illuminate\Support\Str::of($seriesName)->replace('_', ' ')->title();
                                                if (($seriesName === 'target' || $seriesName === 'actual') && !empty($kpiUnit)) {
                                                    $seriesLabel .= ' (' . $kpiUnit . ')';
                                                }
                                            @endphp
                                            <div class="text-gray-800 dark:text-gray-100">{{ $seriesLabel }}</div>
                                        </td>
                                        @foreach($seriesValues as $v)
                                            @php
                                                $tdClass = '';
                                                $tdStyle = '';
                                                $valueTextClass = 'text-gray-800 dark:text-gray-100';
                                                $isDynamicField = \Illuminate\Support\Str::startsWith($seriesName, 'field:');
                                                if ($seriesName === 'target') {
                                                    $tdStyle = 'background-color: rgb(192, 0, 0);';
                                                    $valueTextClass = 'text-white';
                                                } elseif ($seriesName === 'actual') {
                                                    $tdStyle = 'background-color: rgb(0, 112, 192);';
                                                    $valueTextClass = 'text-white';
                                                } elseif ($seriesName === 'status') {
                                                    $tdClass = $v === 'OK'
                                                        ? 'bg-green-50 dark:bg-green-900/20'
                                                        : ($v === 'NG' ? 'bg-red-50 dark:bg-red-900/20' : '');
                                                }
                                            @endphp
                                            <td class="p-2 whitespace-nowrap {{ $tdClass }}" style="{{ $tdStyle }}">
                                                @if($seriesName === 'status')
                                                    @if($v === 'OK')
                                                        <div class="text-center text-xs text-green-700 dark:text-green-300">OK</div>
                                                    @elseif($v === 'NG')
                                                        <div class="text-center text-xs text-red-700 dark:text-red-300">NG</div>
                                                    @else
                                                        <div class="text-center text-xs text-gray-400 dark:text-gray-500"></div>
                                                    @endif
                                                @else
                                                    <div class="text-center {{ $valueTextClass }}">
                                                        {{ $isDynamicField ? ($v ?? '') : $formatKpiCell($v) }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <td class="p-2">
                                        <div class="font-semibold text-left">Summary</div>
                                    </td>
                                    <td class="p-2" colspan="{{ $kpiTableLabels->count() }}">
                                        @php
                                            $unitSuffix = $kpiUnit ? ($kpiUnit === '%' ? '%' : (' ' . $kpiUnit)) : '';
                                        @endphp
                                        <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                            <div><span class="font-semibold">Target</span>: {{ $formatKpiCell($targetValue) ?: '-' }}{{ $unitSuffix }}</div>
                                            <div><span class="font-semibold">Actual Avg</span>: {{ $actualCount ? $formatKpiCell($actualAvg) : '-' }}{{ $unitSuffix }}</div>
                                            <div><span class="font-semibold">OK</span>: {{ $okCount }}</div>
                                            <div><span class="font-semibold">NG</span>: {{ $ngCount }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <script>
                        window.kpiActualTargetChartData = @json($kpiChartData);
                        window.kpiActualTargetChartMeta = @json(array_merge($kpiChartMeta ?? [], [
                            'month_label' => $selectedMonthLabel ?? null,
                        ]));
                    </script>
                </div>
            </div>

            <!-- CAPA Problems Table -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent CAPA Problems</h2>
                </header>
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
                                    <th class="p-2 w-40"><div class="font-semibold text-left">Notes</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-left">PIC</div></th>
                                    <th class="p-2 w-16"><div class="font-semibold text-center">Sev</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-center">Status</div></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @php
                                    $capaGroups = collect($capaProblems)->groupBy(function ($problem) {
                                        $date = optional($problem->area)->capa_date ?? $problem->created_at;
                                        return $date ? $date->format('Y-m-d') : '-';
                                    });

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
                                                        <td class="p-2">
                                                            <div class="text-center">
                                                                <span class="px-2 py-0.5 rounded-full text-xs
                                                                    {{ $actionStatusLabel === 'Closed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($actionStatusLabel === 'In Progress' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : ($actionStatusLabel === 'Open' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200')) }}">
                                                                    {{ $actionStatusLabel }}
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
