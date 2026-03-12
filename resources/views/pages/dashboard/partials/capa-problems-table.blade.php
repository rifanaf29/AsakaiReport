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
                    $capaGroups = collect($capaProblems ?? [])->groupBy(function ($problem) {
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
