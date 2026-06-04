                    @php
                        $kpiTableLabels = collect($kpiChartData['labels'] ?? []);
                        $kpiTableSeries = collect($kpiChartData)
                            ->except('labels')
                            ->filter(fn ($v) => is_iterable($v))
                            ->map(fn ($v) => collect($v));

                        $isDisplayOnly = ($kpiChartMeta['target_mode'] ?? 'with_target') === 'display_only';
                        $dashboardFields = $kpiChartMeta['dashboard_fields'] ?? [];
                        $fieldPalette = [
                            'rgb(0, 112, 192)', 'rgb(192, 0, 0)', 'rgb(0, 176, 80)',
                            'rgb(255, 153, 0)', 'rgb(112, 48, 160)', 'rgb(0, 176, 240)',
                            'rgb(255, 0, 0)', 'rgb(146, 208, 80)',
                        ];
                        // Map chart field keys (in order) to palette colors
                        $chartFieldKeys = !empty($dashboardFields)
                            ? $dashboardFields
                            : array_map(fn($k) => substr($k, 6), array_filter(array_keys($kpiChartData), fn($k) => str_starts_with($k, 'field:')));
                        $dashboardColorMap = [];
                        foreach (array_values($chartFieldKeys) as $i => $fk) {
                            $dashboardColorMap[$fk] = $fieldPalette[$i % count($fieldPalette)];
                        }

                        if ($isDisplayOnly) {
                            $kpiTableSeries = $kpiTableSeries->except(['target', 'actual']);
                        }

                        $isCncWaste = !$isDisplayOnly && (string) ($kpiChartMeta['template_code'] ?? '') === 'TPL_PD_WASTE_CNC_BENDING';
                        $isRejectionProses = !$isDisplayOnly && (string) ($kpiChartMeta['template_code'] ?? '') === 'TPL_PD_REJECTION_PROSES';
                        if ($isCncWaste) {
                            // Waste CNC Bending has no target/status; show Actual + all additional fields.
                            $kpiTableSeries = $kpiTableSeries->except(['target']);
                        }

                        if ($isCncWaste) {
                            // Custom row order for CNC:
                            // Hasil Produksi -> Total Waste (KG) -> Total Waste (%) -> the rest
                            $ordered = collect();

                            if ($kpiTableSeries->has('field:hasil_produksi')) {
                                $ordered['field:hasil_produksi'] = $kpiTableSeries['field:hasil_produksi'];
                            }
                            if ($kpiTableSeries->has('field:total_waste_kg')) {
                                $ordered['field:total_waste_kg'] = $kpiTableSeries['field:total_waste_kg'];
                            }
                            if ($kpiTableSeries->has('actual')) {
                                $ordered['actual'] = $kpiTableSeries['actual'];
                            }

                            $kpiTableSeries = $ordered->merge(
                                $kpiTableSeries->except(['field:hasil_produksi', 'field:total_waste_kg', 'actual'])
                            );
                        }

                        if ($isRejectionProses) {
                            $ordered = collect();
                            foreach (['target', 'actual', 'field:actual_ng', 'field:actual_produksi', 'field:hasil_produksi'] as $key) {
                                if ($kpiTableSeries->has($key)) {
                                    $ordered[$key] = $kpiTableSeries[$key];
                                }
                            }
                            $kpiTableSeries = $ordered->merge(
                                $kpiTableSeries->except(['target', 'actual', 'field:actual_ng', 'field:actual_produksi', 'field:hasil_produksi'])
                            );
                        }

                        $targetSeries = $kpiTableSeries->get('target', collect());
                        $actualSeries = $kpiTableSeries->get('actual', collect());

                        $operator = $kpiChartMeta['target_operator'] ?? 'gte';

                        $statusSeries = collect();
                        if (!$isCncWaste && !$isDisplayOnly) {
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
                        }

                        $kpiUnit = $kpiChartMeta['unit'] ?? null;
                        $showMonthlyTotals = \Illuminate\Support\Str::startsWith((string) ($kpiChartMeta['template_code'] ?? ''), 'TPL_HR_WASTE_');
                        $showCncAverages = $isCncWaste;
                        $cncMoneyFieldKeys = ['d6','d7','d8','d9','d11','d12','d13','copq_material'];
                        $kpiFieldUnits = $kpiChartMeta['field_units'] ?? [];
                        if ($kpiFieldUnits === [] && ! empty($selectedKpiDefinition?->template)) {
                            $kpiFieldUnits = $selectedKpiDefinition->template->fields()->pluck('unit', 'field_key')->all();
                        }
                        $formatKpiCell = function ($value, ?string $rowLabel = null, ?string $unitOverride = null, ?string $fieldKey = null) use ($kpiUnit) {
                            return \App\Support\KpiNumberFormat::format($value, $unitOverride ?? $kpiUnit, $rowLabel, $fieldKey);
                        };

                        $actualNumeric = $actualSeries->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
                        $actualSum = $actualNumeric->sum();
                        $actualCount = $actualNumeric->count();
                        $actualAvg = $actualCount ? ($actualSum / $actualCount) : null;

                        $targetValue = $targetSeries->first(fn ($v) => is_numeric($v));

                        $okCount = $statusSeries->filter(fn ($v) => $v === 'OK')->count();
                        $ngCount = $statusSeries->filter(fn ($v) => $v === 'NG')->count();
                    @endphp

                            <div id="presentation-kpi-table">
                        <table class="table-auto w-full text-xs dark:text-gray-300">
                            <thead class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-2"><div class="font-semibold text-left">Date</div></th>
                                    @foreach($kpiTableLabels as $label)
                                        @php
                                            try {
                                                $labelDate = \Carbon\Carbon::createFromFormat('m-d-Y', $label);
                                                $labelText = $labelDate->format('d');
                                                $dowIso = $labelDate->dayOfWeekIso; // 1=Mon ... 6=Sat, 7=Sun
                                                $dateHeaderCellStyle = $dowIso === 6
                                                    ? 'background-color: rgb(255, 255, 0);'
                                                    : ($dowIso === 7 ? 'background-color: rgb(192, 0, 0);' : '');
                                                $dateHeaderTextClass = $dowIso === 7 ? 'text-white' : '';
                                            } catch (\Exception $e) {
                                                $labelText = $label;
                                                $dateHeaderCellStyle = '';
                                                $dateHeaderTextClass = '';
                                            }
                                        @endphp
                                        <th class="p-2 whitespace-nowrap" style="{{ $dateHeaderCellStyle }}"><div class="font-semibold text-center {{ $dateHeaderTextClass }}">{{ $labelText }}</div></th>
                                    @endforeach
                                    @if($showMonthlyTotals)
                                        <th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Total</div></th>
                                    @endif
                                    @if($showCncAverages)
                                        <th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Average</div></th>
                                        <th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Average/D</div></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @foreach($kpiTableSeries as $seriesName => $seriesValues)
                                    @php
                                        $isCncTotalRow = $isCncWaste && in_array($seriesName, ['field:total_waste_kg', 'actual'], true);

                                        $numericValues = collect($seriesValues)->filter(fn ($x) => is_numeric($x))->map(fn ($x) => (float) $x);
                                        $avgValue = $numericValues->count() ? ($numericValues->sum() / $numericValues->count()) : null;
                                        $isCncMoneyRow = $isCncWaste
                                            && \Illuminate\Support\Str::startsWith($seriesName, 'field:')
                                            && in_array(substr($seriesName, 6), $cncMoneyFieldKeys, true);
                                        $avgDValue = $isCncMoneyRow ? ($numericValues->count() ? $numericValues->sum() : null) : $avgValue;

                                        $fieldKey = str_starts_with($seriesName, 'field:') ? substr($seriesName, 6) : null;
                                        $rowFieldUnit = $fieldKey ? ($kpiFieldUnits[$fieldKey] ?? null) : null;
                                        $isChartField = $isDisplayOnly && $fieldKey && isset($dashboardColorMap[$fieldKey]);
                                        $chartColor = $isChartField ? $dashboardColorMap[$fieldKey] : null;
                                    @endphp
                                    <tr @if($rowFieldUnit) data-kpi-unit="{{ strtolower($rowFieldUnit) }}" @endif @if($fieldKey) data-kpi-field-key="{{ $fieldKey }}" @endif @if($isChartField) style="background-color: {{ preg_replace('/^rgb\((.+)\)$/', 'rgba($1, 0.07)', $chartColor) }};" @endif>
                                        <td class="p-2" @if($isCncTotalRow) style="background-color: rgb(192, 0, 0);" @elseif($isChartField) style="border-left: 4px solid {{ $chartColor }}; padding-left: 8px;" @endif>
                                            @php
                                                $seriesLabel = $kpiSeriesLabels[$seriesName] ?? \Illuminate\Support\Str::of($seriesName)->replace('_', ' ')->title();
                                                if ($fieldKey === 'hasil_produksi' && ! str_contains(strtolower($seriesLabel), 'kg')) {
                                                    $seriesLabel = 'Hasil Produksi (Kg)';
                                                }
                                                if (($seriesName === 'target' || $seriesName === 'actual') && !empty($kpiUnit)) {
                                                    $seriesLabel .= ' (' . $kpiUnit . ')';
                                                }
                                            @endphp
                                            <div class="{{ $isCncTotalRow ? 'text-white' : 'text-gray-800 dark:text-gray-100' }}">{{ $seriesLabel }}</div>
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

                                                if ($isCncTotalRow) {
                                                    $tdStyle = 'background-color: rgb(192, 0, 0);';
                                                    $valueTextClass = 'text-white';
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
                                                    @php
                                                        $displayValue = $v;
                                                        if ($fieldKey === 'hasil_produksi' && is_numeric($v) && (float) $v == 0.0) {
                                                            $displayValue = null;
                                                        }
                                                    @endphp
                                                    <div class="text-center {{ $valueTextClass }}">
                                                        {{ is_numeric($displayValue) ? $formatKpiCell($displayValue, $seriesLabel, $rowFieldUnit, $fieldKey) : ($displayValue ?? '') }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach

                                        @if($showMonthlyTotals)
                                            @php
                                                $isDynamicField = \Illuminate\Support\Str::startsWith($seriesName, 'field:');
                                                $total = null;
                                                if ($seriesName === 'actual') {
                                                    $totalValues = collect($seriesValues)->filter(fn ($x) => is_numeric($x));
                                                    $total = $totalValues->isNotEmpty()
                                                        ? $totalValues->map(fn ($x) => (float) $x)->sum()
                                                        : null;
                                                } elseif ($isDynamicField) {
                                                    $totalValues = collect($seriesValues)->filter(fn ($x) => is_numeric($x));
                                                    if ($seriesName === 'field:hasil_produksi') {
                                                        $totalValues = $totalValues->filter(fn ($x) => (float) $x != 0.0);
                                                    }
                                                    $total = $totalValues->isNotEmpty()
                                                        ? $totalValues->map(fn ($x) => (float) $x)->sum()
                                                        : null;
                                                }
                                            @endphp
                                            <td class="p-2 whitespace-nowrap">
                                                <div class="text-center text-gray-800 dark:text-gray-100">
                                                    {{ $isDynamicField && $total !== null ? $formatKpiCell($total, $seriesLabel, $rowFieldUnit, $fieldKey) : '' }}
                                                </div>
                                            </td>
                                        @endif

                                        @if($showCncAverages)
                                            <td class="p-2 whitespace-nowrap" @if($isCncTotalRow) style="background-color: rgb(192, 0, 0);" @endif>
                                                <div class="text-center {{ $isCncTotalRow ? 'text-white' : 'text-gray-800 dark:text-gray-100' }}">
                                                    {{ $avgValue !== null ? $formatKpiCell($avgValue, $seriesLabel, $rowFieldUnit, $fieldKey) : '' }}
                                                </div>
                                            </td>
                                            <td class="p-2 whitespace-nowrap" @if($isCncTotalRow) style="background-color: rgb(192, 0, 0);" @endif>
                                                <div class="text-center {{ $isCncTotalRow ? 'text-white' : 'text-gray-800 dark:text-gray-100' }}">
                                                    {{ $avgDValue !== null ? $formatKpiCell($avgDValue, $seriesLabel, $rowFieldUnit, $fieldKey) : '' }}
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            @if(!$isCncWaste)
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
                                            <div><span class="font-semibold">Target</span>: {{ $formatKpiCell($targetValue, 'Target (' . ($kpiUnit ?? '') . ')') ?: '-' }}{{ $unitSuffix }}</div>
                                            <div><span class="font-semibold">Actual Avg</span>: {{ $actualCount ? $formatKpiCell($actualAvg, 'Actual (' . ($kpiUnit ?? '') . ')') : '-' }}{{ $unitSuffix }}</div>
                                            <div><span class="font-semibold">OK</span>: {{ $okCount }}</div>
                                            <div><span class="font-semibold">NG</span>: {{ $ngCount }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                            </div>
