@php
    $exitUrl = route('dashboard', request()->except('fullscreen'));
@endphp

<x-fullscreen-layout>
    <div class="p-4 sm:p-6 lg:p-8 w-full max-w mx-auto">
        <div class="grid grid-cols-12 gap-6">
            <!-- KPI Actual vs Target Chart -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-800 dark:text-gray-100">KPI Actual vs Target</h2>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $selectedMonthLabel ?? '' }}{{ ($selectedMonthLabel ?? null) ? ' • ' : '' }}{{ $kpiChartMeta['template_title'] ?? ($selectedTemplate ? $selectedTemplate->name : 'All Templates') }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}
                            </div>
                        </div>
                        <a href="{{ $exitUrl }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Exit</a>
                    </div>
                </header>
                <div class="p-5">
                    <div class="h-[260px]">
                        <canvas id="kpi-actual-target-chart" height="260"></canvas>
                    </div>

                    @php
                        $kpiTableLabels = collect($kpiChartData['labels'] ?? []);
                        $kpiTableSeries = collect($kpiChartData)
                            ->except('labels')
                            ->filter(fn ($v) => is_iterable($v))
                            ->map(fn ($v) => collect($v));

                        $targetSeries = $kpiTableSeries->get('target', collect());
                        $actualSeries = $kpiTableSeries->get('actual', collect());

                        $statusSeries = $kpiTableLabels->values()->map(function ($_, $index) use ($targetSeries, $actualSeries) {
                            $target = $targetSeries->get($index);
                            $actual = $actualSeries->get($index);

                            if ($target === null || $target === '' || $actual === null || $actual === '') return null;
                            if (!is_numeric($target) || !is_numeric($actual)) return null;

                            return ((float) $actual >= (float) $target) ? 'OK' : 'NG';
                        });

                        $kpiTableSeries = $kpiTableSeries->merge([
                            'status' => $statusSeries,
                        ]);

                        $kpiUnit = $kpiChartMeta['unit'] ?? null;
                        $formatKpiCell = function ($value) use ($kpiUnit) {
                            if ($value === null || $value === '') return '';
                            if (!is_numeric($value)) return '';
                            $number = (float) $value;
                            $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
                            if (!$kpiUnit) return $formatted;
                            if ($kpiUnit === '%') return $formatted . '%';
                            return $formatted . ' ' . $kpiUnit;
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
                                                $dateHeaderCellClass = $dowIso === 6
                                                    ? 'bg-yellow-50 dark:bg-yellow-900/20'
                                                    : ($dowIso === 7 ? 'bg-red-50 dark:bg-red-900/20' : '');
                                            } catch (\Exception $e) {
                                                $labelText = $label;
                                                $dateHeaderCellClass = '';
                                            }
                                        @endphp
                                        <th class="p-2 whitespace-nowrap {{ $dateHeaderCellClass }}"><div class="font-semibold text-center">{{ $labelText }}</div></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @foreach($kpiTableSeries as $seriesName => $seriesValues)
                                    <tr>
                                        <td class="p-2">
                                            <div class="text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::of($seriesName)->replace('_', ' ')->title() }}</div>
                                        </td>
                                        @foreach($seriesValues as $v)
                                            @php
                                                $tdClass = '';
                                                if ($seriesName === 'target') {
                                                    $tdClass = 'bg-red-50 dark:bg-red-900/20';
                                                } elseif ($seriesName === 'actual') {
                                                    $tdClass = 'bg-blue-50 dark:bg-blue-900/20';
                                                } elseif ($seriesName === 'status') {
                                                    $tdClass = $v === 'OK'
                                                        ? 'bg-green-50 dark:bg-green-900/20'
                                                        : ($v === 'NG' ? 'bg-red-50 dark:bg-red-900/20' : '');
                                                }
                                            @endphp
                                            <td class="p-2 whitespace-nowrap {{ $tdClass }}">
                                                @if($seriesName === 'status')
                                                    @if($v === 'OK')
                                                        <div class="text-center text-xs text-green-700 dark:text-green-300">OK</div>
                                                    @elseif($v === 'NG')
                                                        <div class="text-center text-xs text-red-700 dark:text-red-300">NG</div>
                                                    @else
                                                        <div class="text-center text-xs text-gray-400 dark:text-gray-500"></div>
                                                    @endif
                                                @else
                                                    <div class="text-center text-gray-800 dark:text-gray-100">{{ $formatKpiCell($v) }}</div>
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
                                        <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                            <div><span class="font-semibold">Target</span>: {{ $formatKpiCell($targetValue) ?: '-' }}</div>
                                            <div><span class="font-semibold">Actual Sum</span>: {{ $actualCount ? $formatKpiCell($actualSum) : '-' }}</div>
                                            <div><span class="font-semibold">Actual Avg</span>: {{ $actualCount ? $formatKpiCell($actualAvg) : '-' }}</div>
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
                        window.kpiActualTargetChartMeta = @json($kpiChartMeta);
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
                                    <th class="p-2 w-28"><div class="font-semibold text-left">Date</div></th>
                                    <th class="p-2 w-32"><div class="font-semibold text-left">Area</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Problem</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Root Cause</div></th>
                                    <th class="p-2 w-80"><div class="font-semibold text-left">Action Plan</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-left">Due Date</div></th>
                                    <th class="p-2 w-40"><div class="font-semibold text-left">Notes</div></th>
                                    <th class="p-2 w-28"><div class="font-semibold text-left">PIC</div></th>
                                    <th class="p-2 w-24"><div class="font-semibold text-center">Severity</div></th>
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
                                                            <td class="p-2 align-top bg-gray-50 dark:bg-gray-700/20" rowspan="{{ $dateRowspan }}">
                                                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $dateKey }}</div>
                                                            </td>
                                                            @php($printedDate = true)
                                                        @endif

                                                        @if(!$printedArea)
                                                            <td class="p-2 align-top bg-gray-50 dark:bg-gray-700/10" rowspan="{{ $areaRowspan }}">
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
                                                            <div class="text-center">
                                                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                                    {{ ucfirst($problem->severity) }}
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
</x-fullscreen-layout>