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
                            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <span class="inline-flex shrink-0 text-blue-600 dark:text-blue-400" aria-hidden="true">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </span>
                                <span>KPI Actual vs Target</span>
                            </h2>
                            <div id="presentation-subtitle" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $selectedMonthLabel ?? '' }}{{ ($selectedMonthLabel ?? null) ? ' • ' : '' }}{{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 min-w-0">
                            <select id="presentation-department" class="text-sm border-gray-200 rounded-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ (int) ($selectedDepartmentId ?? 0) === (int) $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>

                            <input id="presentation-month" type="month" value="{{ $selectedMonth ?? now()->format('Y-m') }}" class="text-sm border-gray-200 rounded-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />

                            <button id="presentation-prev" type="button" class="text-sm px-3 py-1.5 border border-gray-200 rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/30">
                                Prev
                            </button>

                            <select id="presentation-kpi" class="text-sm border-gray-200 rounded-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 w-72 max-w-[18rem] min-w-0 truncate">
                                @foreach($kpis as $kpi)
                                    <option value="{{ $kpi->id }}" {{ (int) ($selectedKpiDefinitionId ?? 0) === (int) $kpi->id ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($kpi->display_name ?: ($kpi->template?->code ?: 'KPI'), 60) }}
                                    </option>
                                @endforeach
                            </select>

                            <button id="presentation-next" type="button" class="text-sm px-3 py-1.5 border border-gray-200 rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/30">
                                Next
                            </button>

                            <a href="{{ $exitUrl }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Exit</a>
                        </div>
                    </div>
                </header>
                <div class="p-5">
                    <div id="kpi-chart-table-sync" class="overflow-x-auto">
                        <div id="kpi-chart-table-sync-inner">
                            <div class="kpi-chart-table-wrap shrink-0 overflow-hidden mb-2" style="height:300px;min-height:300px;max-height:300px;box-sizing:border-box;">
                                <canvas id="kpi-actual-target-chart" height="300" width="800"></canvas>
                            </div>

                    @php
                        $kpiTableLabels = collect($kpiChartData['labels'] ?? []);
                        $kpiTableSeries = collect($kpiChartData)
                            ->except('labels')
                            ->filter(fn ($v) => is_iterable($v))
                            ->map(fn ($v) => collect($v));

                        $isDisplayOnly = ($kpiChartMeta['target_mode'] ?? 'with_target') === 'display_only';

                        if ($isDisplayOnly) {
                            $kpiTableSeries = $kpiTableSeries->except(['target', 'actual']);
                            $statusSeries = collect();
                        } else {
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
                        }

                        $dashboardFields = $kpiChartMeta['dashboard_fields'] ?? [];
                        $fieldPalette = [
                            'rgb(0, 112, 192)', 'rgb(192, 0, 0)', 'rgb(0, 176, 80)',
                            'rgb(255, 153, 0)', 'rgb(112, 48, 160)', 'rgb(0, 176, 240)',
                            'rgb(255, 0, 0)', 'rgb(146, 208, 80)',
                        ];
                        $chartFieldKeys = !empty($dashboardFields)
                            ? $dashboardFields
                            : array_map(fn($k) => substr($k, 6), array_filter(array_keys($kpiChartData), fn($k) => str_starts_with($k, 'field:')));
                        $dashboardColorMap = [];
                        foreach (array_values($chartFieldKeys) as $i => $fk) {
                            $dashboardColorMap[$fk] = $fieldPalette[$i % count($fieldPalette)];
                        }

                        $kpiUnit = $kpiChartMeta['unit'] ?? null;
                        $showMonthlyTotals = \Illuminate\Support\Str::startsWith((string) ($kpiChartMeta['template_code'] ?? ''), 'TPL_HR_WASTE_');
                        $kpiFieldUnits = $kpiChartMeta['field_units'] ?? [];
                        if ($kpiFieldUnits === [] && ! empty($selectedKpiDefinition?->template)) {
                            $kpiFieldUnits = $selectedKpiDefinition->template->fields()->pluck('unit', 'field_key')->all();
                        }
                        $formatKpiCell = function ($value, ?string $rowLabel = null, ?string $unitOverride = null, ?string $fieldKey = null) use ($kpiUnit) {
                            return \App\Support\KpiNumberFormat::format($value, $unitOverride ?? $kpiUnit, $rowLabel, $fieldKey);
                        };

                        $actualSeries = $actualSeries ?? collect();
                        $targetSeries = $targetSeries ?? collect();
                        $actualNumeric = $actualSeries->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
                        $actualSum = $actualNumeric->sum();
                        $actualCount = $actualNumeric->count();
                        $actualAvg = $actualCount ? ($actualSum / $actualCount) : null;

                        $targetValue = $targetSeries->first(fn ($v) => is_numeric($v));

                        $okCount = $statusSeries->filter(fn ($v) => $v === 'OK')->count();
                        $ngCount = $statusSeries->filter(fn ($v) => $v === 'NG')->count();

                        $kpiChartMetaJs = array_merge($kpiChartMeta ?? [], [
                            'month_label' => $selectedMonthLabel ?? null,
                            'field_units' => $kpiFieldUnits,
                        ]);
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
                                </tr>
                            </thead>
                            <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @foreach($kpiTableSeries as $seriesName => $seriesValues)
                                    @php
                                        $fieldKey = str_starts_with($seriesName, 'field:') ? substr($seriesName, 6) : null;
                                        $isChartField = $isDisplayOnly && $fieldKey && isset($dashboardColorMap[$fieldKey]);
                                        $chartColor = $isChartField ? $dashboardColorMap[$fieldKey] : null;
                                    @endphp
                                    @php
                                        $rowFieldUnit = $fieldKey ? ($kpiFieldUnits[$fieldKey] ?? null) : null;
                                    @endphp
                                    <tr @if($rowFieldUnit) data-kpi-unit="{{ strtolower($rowFieldUnit) }}" @endif @if($fieldKey) data-kpi-field-key="{{ $fieldKey }}" @endif @if($isChartField) style="background-color: {{ preg_replace('/^rgb\((.+)\)$/', 'rgba($1, 0.07)', $chartColor) }};" @endif>
                                        <td class="p-2" @if($isChartField) style="border-left: 4px solid {{ $chartColor }}; padding-left: 8px;" @endif>
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
                                                $tdStyle = '';
                                                $tdClass = '';
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
                                                        {{ is_numeric($v) ? $formatKpiCell($v, $seriesLabel, $rowFieldUnit, $fieldKey) : ($v ?? '') }}
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
                                                    {{ $total !== null ? $formatKpiCell($total, $seriesLabel, $rowFieldUnit, $fieldKey) : '' }}
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            @if(!$isDisplayOnly)
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
                                            <div><span class="font-semibold">Actual Sum</span>: {{ $actualCount ? $formatKpiCell($actualSum, 'Actual (' . ($kpiUnit ?? '') . ')') : '-' }}{{ $unitSuffix }}</div>
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
                        </div>
                    </div>

                    <script>
                        window.kpiActualTargetChartData = @json($kpiChartData);
                        window.kpiActualTargetChartMeta = @json($kpiChartMetaJs);
                        window.kpiSeriesLabels = @json($kpiSeriesLabels ?? []);
                        window.presentationDashboardPayloadUrl = @json(route('dashboard.payload'));
                    </script>
                </div>
            </div>

            <script>
                (function () {
                    const payloadUrl = window.presentationDashboardPayloadUrl;
                    if (!payloadUrl) return;

                    const elSubtitle = document.getElementById('presentation-subtitle');
                    const elDept = document.getElementById('presentation-department');
                    const elMonth = document.getElementById('presentation-month');
                    const elKpi = document.getElementById('presentation-kpi');
                    const btnPrev = document.getElementById('presentation-prev');
                    const btnNext = document.getElementById('presentation-next');
                    const elTableWrapper = document.getElementById('presentation-kpi-table');
                    let selectedCapaStatus = @json($selectedCapaStatus ?? null);

                    if (!elMonth || !elKpi || !btnPrev || !btnNext || !elTableWrapper) return;

                    const escapeHtml = (value) => {
                        return String(value ?? '')
                            .replaceAll('&', '&amp;')
                            .replaceAll('<', '&lt;')
                            .replaceAll('>', '&gt;')
                            .replaceAll('"', '&quot;')
                            .replaceAll("'", '&#039;');
                    };

                    const toIntOrNull = (value) => {
                        const n = Number.parseInt(String(value ?? ''), 10);
                        return Number.isFinite(n) ? n : null;
                    };

                    const resolveKpiFormatKind = (unit, rowLabel, fieldKey) => {
                        const u = String(unit ?? '').trim().toLowerCase();
                        const label = String(rowLabel ?? '').toLowerCase();
                        const fk = String(fieldKey ?? '').toLowerCase();
                        if (u === 'ppm' || label.includes('(ppm)') || /\bppm\b/.test(label)) return 'ppm';
                        if (u === '%' || label.includes('(%)')) return 'percent';
                        if (u === 'kg' || label.includes('(kg)') || label.includes('gram')) return 'default';
                        if (u === 'pcs' || u === 'pc' || label.includes('(pcs)') || label.includes('(pc)')) return 'pcs';
                        if (fk && (fk.endsWith('_pcs') || fk.endsWith('_ng') || ['actual_produksi', 'actual_ng', 'order_pcs', 'shortage_pcs'].includes(fk))) return 'pcs';
                        return 'default';
                    };

                    const formatKpiNumber = (value, unit, rowLabel, fieldKey) => {
                        const n = Number(value);
                        if (!Number.isFinite(n)) return '';
                        const kind = resolveKpiFormatKind(unit, rowLabel, fieldKey);
                        if (kind === 'ppm' || kind === 'pcs') {
                            return new Intl.NumberFormat('id-ID', { useGrouping: true, maximumFractionDigits: 0 }).format(Math.round(n));
                        }
                        if (kind === 'percent') {
                            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }).format(n);
                        }
                        let formatted = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n);
                        return formatted.replace(/,00$/, '').replace(/,(\d)0$/, ',$1');
                    };

                    const parseLabelDate = (label) => {
                        const parts = String(label ?? '').split('-');
                        if (parts.length !== 3) return null;
                        const m = Number.parseInt(parts[0], 10);
                        const d = Number.parseInt(parts[1], 10);
                        const y = Number.parseInt(parts[2], 10);
                        if (!Number.isFinite(m) || !Number.isFinite(d) || !Number.isFinite(y)) return null;
                        return new Date(y, m - 1, d);
                    };

                    const computeStatus = (target, actual, operator) => {
                        if (target === null || target === '' || actual === null || actual === '') return null;
                        const t = Number(target);
                        const a = Number(actual);
                        if (!Number.isFinite(t) || !Number.isFinite(a)) return null;
                        if (operator === 'lte') return a <= t ? 'OK' : 'NG';
                        return a >= t ? 'OK' : 'NG';
                    };

                    const buildSubtitleText = (monthLabel, title, unit) => {
                        const parts = [];
                        if (monthLabel) parts.push(monthLabel);
                        if (title) parts.push(title);
                        if (unit) parts.push(`Unit: ${unit}`);
                        return parts.join(' • ');
                    };

                    const buildKpiTableHtml = (payload) => {
                        const chartData = payload?.kpiChartData || {};
                        const labels = Array.isArray(chartData.labels) ? chartData.labels : [];

                        const seriesLabels = payload?.kpiSeriesLabels || {};
                        const meta = payload?.kpiChartMeta || {};
                        const unit = meta.unit || null;
                        const fieldUnits = meta.field_units || {};
                        const formatNumber = (value, rowLabel = null, rowUnit = null, fieldKey = null) => {
                            return formatKpiNumber(value, rowUnit ?? unit, rowLabel, fieldKey);
                        };
                        const operator = meta.target_operator || 'gte';
                        const templateCode = String(meta.template_code || '');
                        const isCncWaste = templateCode === 'TPL_PD_WASTE_CNC_BENDING';
                        const isDisplayOnly = meta.target_mode === 'display_only';
                        const showMonthlyTotals = !isCncWaste && !isDisplayOnly && templateCode.startsWith('TPL_HR_WASTE_');
                        const showCncAverages = isCncWaste;
                        const cncMoneyKeys = new Set(['d6', 'd7', 'd8', 'd9', 'd11', 'd12', 'd13', 'copq_material']);

                        const target = Array.isArray(chartData.target) ? chartData.target : [];
                        const actual = Array.isArray(chartData.actual) ? chartData.actual : [];

                        const allFieldKeys = Object.keys(chartData).filter((k) => k.startsWith('field:'));
                        const fieldKeys = allFieldKeys;

                        const fieldPalette = [
                            'rgb(0, 112, 192)', 'rgb(192, 0, 0)', 'rgb(0, 176, 80)',
                            'rgb(255, 153, 0)', 'rgb(112, 48, 160)', 'rgb(0, 176, 240)',
                            'rgb(255, 0, 0)', 'rgb(146, 208, 80)',
                        ];
                        const dashboardFields = Array.isArray(meta.dashboard_fields) ? meta.dashboard_fields : [];
                        const chartFieldKeys = isDisplayOnly
                            ? (dashboardFields.length > 0 ? dashboardFields : allFieldKeys.map((k) => k.slice(6)))
                            : [];
                        const fieldColorMap = {};
                        chartFieldKeys.forEach((key, i) => {
                            fieldColorMap[key] = fieldPalette[i % fieldPalette.length];
                        });

                        const cncSeriesOrder = (() => {
                            const wanted = ['field:hasil_produksi', 'field:total_waste_kg', 'actual'];
                            const rest = ['actual', ...fieldKeys].filter((k) => !wanted.includes(k));
                            return [...wanted.filter((k) => k === 'actual' || fieldKeys.includes(k)), ...rest];
                        })();

                        const seriesOrder = isCncWaste
                            ? cncSeriesOrder
                            : (isDisplayOnly
                                ? [...fieldKeys]
                                : ['target', 'actual', ...fieldKeys, 'status']);

                        const status = (isCncWaste || isDisplayOnly) ? [] : labels.map((_, i) => computeStatus(target[i], actual[i], operator));

                        const actualNumeric = actual
                            .map((v) => Number(v))
                            .filter((v) => Number.isFinite(v));
                        const actualSum = actualNumeric.reduce((acc, v) => acc + v, 0);
                        const actualAvg = actualNumeric.length ? (actualSum / actualNumeric.length) : null;
                        const targetValue = target.map((v) => Number(v)).find((v) => Number.isFinite(v));
                        const okCount = isCncWaste ? 0 : status.filter((v) => v === 'OK').length;
                        const ngCount = isCncWaste ? 0 : status.filter((v) => v === 'NG').length;
                        const unitSuffix = unit ? (unit === '%' ? '%' : ` ${unit}`) : '';

                        const headerCells = labels
                            .map((label) => {
                                const dt = parseLabelDate(label);
                                let text = escapeHtml(label);
                                let style = '';
                                let textClass = '';
                                if (dt) {
                                    text = String(dt.getDate()).padStart(2, '0');
                                    const dow = dt.getDay(); // 0 Sun, 6 Sat
                                    if (dow === 6) {
                                        style = 'background-color: rgb(255, 255, 0);';
                                        textClass = '';
                                    } else if (dow === 0) {
                                        style = 'background-color: rgb(192, 0, 0);';
                                        textClass = 'text-white';
                                    }
                                }
                                return `<th class="p-2 whitespace-nowrap" style="${style}"><div class="font-semibold text-center ${textClass}">${escapeHtml(text)}</div></th>`;
                            })
                            .join('');

                        const buildRow = (seriesName) => {
                            const values = seriesName === 'status'
                                ? status
                                : (Array.isArray(chartData[seriesName]) ? chartData[seriesName] : []);

                            let label = seriesLabels[seriesName];
                            if (!label) {
                                label = seriesName.replaceAll('_', ' ');
                                label = label.replace(/^\w/, (c) => c.toUpperCase());
                            }
                            if ((seriesName === 'target' || seriesName === 'actual') && unit) {
                                label = `${label} (${unit})`;
                            }

                            const fieldKey = seriesName.startsWith('field:') ? seriesName.slice(6) : null;
                            const rowUnit = fieldKey
                                ? (fieldUnits[fieldKey] || null)
                                : ((seriesName === 'target' || seriesName === 'actual') ? unit : null);
                            const rowDataAttrs = [
                                fieldKey ? `data-kpi-field-key="${escapeHtml(fieldKey)}"` : '',
                                rowUnit ? `data-kpi-unit="${escapeHtml(String(rowUnit).toLowerCase())}"` : '',
                            ].filter(Boolean).join(' ');

                            const cells = labels.map((_, idx) => {
                                const v = values[idx];
                                let tdStyle = '';
                                let tdClass = '';
                                let valueTextClass = 'text-gray-800 dark:text-gray-100';

                                const isCncTotalRow = isCncWaste && (seriesName === 'field:total_waste_kg' || seriesName === 'actual');

                                if (seriesName === 'target') {
                                    tdStyle = 'background-color: rgb(192, 0, 0);';
                                    valueTextClass = 'text-white';
                                } else if (seriesName === 'actual') {
                                    tdStyle = 'background-color: rgb(0, 112, 192);';
                                    valueTextClass = 'text-white';
                                } else if (seriesName === 'status') {
                                    tdClass = v === 'OK'
                                        ? 'bg-green-50 dark:bg-green-900/20'
                                        : (v === 'NG' ? 'bg-red-50 dark:bg-red-900/20' : '');
                                }

                                if (isCncTotalRow) {
                                    tdStyle = 'background-color: rgb(192, 0, 0);';
                                    valueTextClass = 'text-white';
                                }

                                if (seriesName === 'status') {
                                    const statusText = v === 'OK' ? '<div class="text-center text-xs text-green-700 dark:text-green-300">OK</div>'
                                        : (v === 'NG' ? '<div class="text-center text-xs text-red-700 dark:text-red-300">NG</div>'
                                        : '<div class="text-center text-xs text-gray-400 dark:text-gray-500"></div>');
                                    return `<td class="p-2 whitespace-nowrap ${tdClass}" style="${tdStyle}">${statusText}</td>`;
                                }

                                const displayValue = formatNumber(v, label, rowUnit, fieldKey);
                                return `<td class="p-2 whitespace-nowrap ${tdClass}" style="${tdStyle}"><div class="text-center ${valueTextClass}">${escapeHtml(displayValue)}</div></td>`;
                            }).join('');

                            let avgCells = '';
                            if (showCncAverages) {
                                const nums = values.map((v) => Number(v)).filter((n) => Number.isFinite(n));
                                const avg = nums.length ? (nums.reduce((a, n) => a + n, 0) / nums.length) : null;

                                const isMoneyRow = seriesName.startsWith('field:') && cncMoneyKeys.has(seriesName.slice(6));
                                const avgD = isMoneyRow
                                    ? (nums.length ? nums.reduce((a, n) => a + n, 0) : null)
                                    : avg;

                                const isCncTotalRow = isCncWaste && (seriesName === 'field:total_waste_kg' || seriesName === 'actual');
                                const tdStyle = isCncTotalRow ? 'background-color: rgb(192, 0, 0);' : '';
                                const textClass = isCncTotalRow ? 'text-white' : 'text-gray-800 dark:text-gray-100';

                                avgCells = `
                                    <td class="p-2 whitespace-nowrap" style="${tdStyle}"><div class="text-center ${textClass}">${escapeHtml(avg === null ? '' : formatNumber(avg, label, rowUnit, fieldKey))}</div></td>
                                    <td class="p-2 whitespace-nowrap" style="${tdStyle}"><div class="text-center ${textClass}">${escapeHtml(avgD === null ? '' : formatNumber(avgD, label, rowUnit, fieldKey))}</div></td>
                                `;
                            }

                            let totalCell = '';
                            if (showMonthlyTotals) {
                                let sum = null;
                                if (seriesName === 'actual') {
                                    const nums = values.map((v) => Number(v)).filter((n) => Number.isFinite(n));
                                    sum = nums.length ? nums.reduce((acc, n) => acc + n, 0) : null;
                                } else if (seriesName.startsWith('field:')) {
                                    let nums = values.map((v) => Number(v)).filter((n) => Number.isFinite(n));
                                    if (seriesName === 'field:hasil_produksi') {
                                        nums = nums.filter((n) => n !== 0);
                                    }
                                    sum = nums.length ? nums.reduce((acc, n) => acc + n, 0) : null;
                                }
                                totalCell = sum !== null
                                    ? `<td class="p-2 whitespace-nowrap"><div class="text-center text-gray-800 dark:text-gray-100">${escapeHtml(formatNumber(sum, label, rowUnit, fieldKey))}</div></td>`
                                    : `<td class="p-2 whitespace-nowrap"><div class="text-center"></div></td>`;
                            }

                            const fieldKeyPlain = seriesName.startsWith('field:') ? seriesName.slice(6) : null;
                            const isChartField = isDisplayOnly && fieldKeyPlain && fieldColorMap[fieldKeyPlain];
                            const chartColor = isChartField ? fieldColorMap[fieldKeyPlain] : null;
                            const trRowStyle = isChartField
                                ? `background-color: ${chartColor.replace('rgb(', 'rgba(').replace(')', ', 0.07)')};`
                                : '';
                            const isCncHighlight = isCncWaste && (seriesName === 'field:total_waste_kg' || seriesName === 'actual');
                            const firstTdStyle = isCncHighlight
                                ? 'background-color: rgb(192, 0, 0);'
                                : (isChartField ? `border-left: 4px solid ${chartColor}; padding-left: 8px;` : '');
                            const firstTdTextClass = isCncHighlight ? 'text-white' : 'text-gray-800 dark:text-gray-100';

                            return `
                                <tr ${rowDataAttrs} style="${trRowStyle}">
                                    <td class="p-2" style="${firstTdStyle}"><div class="${firstTdTextClass}">${escapeHtml(label)}</div></td>
                                    ${cells}
                                    ${totalCell}
                                    ${avgCells}
                                </tr>
                            `;
                        };

                        const bodyRows = seriesOrder.map(buildRow).join('');

                        const summaryTarget = Number.isFinite(targetValue) ? `${formatNumber(targetValue, `Target (${unit || ''})`)}${unitSuffix}` : `-${unitSuffix}`;
                        const summarySum = actualNumeric.length ? `${formatNumber(actualSum, `Actual (${unit || ''})`)}${unitSuffix}` : `-${unitSuffix}`;
                        const summaryAvg = actualNumeric.length && Number.isFinite(actualAvg) ? `${formatNumber(actualAvg, `Actual (${unit || ''})`)}${unitSuffix}` : `-${unitSuffix}`;

                        return `
                            <table class="table-auto w-full text-xs dark:text-gray-300">
                                <thead class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="p-2"><div class="font-semibold text-left">Date</div></th>
                                        ${headerCells}
                                        ${showMonthlyTotals ? '<th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Total</div></th>' : ''}
                                        ${showCncAverages ? '<th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Average</div></th><th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Average/D</div></th>' : ''}
                                    </tr>
                                </thead>
                                <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                    ${bodyRows}
                                </tbody>
                                ${(isCncWaste || isDisplayOnly) ? '' : `
                                <tfoot class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <td class="p-2"><div class="font-semibold text-left">Summary</div></td>
                                        <td class="p-2" colspan="${labels.length + (showMonthlyTotals ? 1 : 0)}">
                                            <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                                <div><span class="font-semibold">Target</span>: ${escapeHtml(summaryTarget)}</div>
                                                <div><span class="font-semibold">Actual Sum</span>: ${escapeHtml(summarySum)}</div>
                                                <div><span class="font-semibold">Actual Avg</span>: ${escapeHtml(summaryAvg)}</div>
                                                <div><span class="font-semibold">OK</span>: ${okCount}</div>
                                                <div><span class="font-semibold">NG</span>: ${ngCount}</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                                `}
                            </table>
                        `;
                    };

                    let inFlight = false;
                    let currentKpis = Array.from(elKpi.options).map((opt) => ({
                        id: toIntOrNull(opt.value),
                        name: opt.textContent,
                    })).filter((k) => k.id);

                    const renderKpiOptions = (kpis, selectedId) => {
                        elKpi.innerHTML = '';
                        kpis.forEach((kpi) => {
                            const opt = document.createElement('option');
                            opt.value = String(kpi.id);
                            opt.textContent = kpi.name;
                            if (selectedId && Number(kpi.id) === Number(selectedId)) opt.selected = true;
                            elKpi.appendChild(opt);
                        });
                    };

                    const applyPayload = (payload) => {
                        currentKpis = Array.isArray(payload?.kpis) ? payload.kpis : currentKpis;

                        const selectedId = payload?.selectedKpiDefinitionId ? Number(payload.selectedKpiDefinitionId) : null;
                        if (Array.isArray(payload?.kpis) && payload.kpis.length) {
                            const stillExists = selectedId && payload.kpis.some((k) => Number(k.id) === Number(selectedId));
                            renderKpiOptions(payload.kpis, stillExists ? selectedId : Number(payload.kpis[0].id));
                        }

                        window.kpiActualTargetChartData = payload.kpiChartData;
                        window.kpiActualTargetChartMeta = {
                            ...(payload.kpiChartMeta || {}),
                            field_units: payload.kpiChartMeta?.field_units
                                || window.kpiActualTargetChartMeta?.field_units
                                || {},
                        };
                        window.kpiSeriesLabels = payload.kpiSeriesLabels || {};

                        if (elSubtitle) {
                            elSubtitle.textContent = buildSubtitleText(payload.selectedMonthLabel, payload?.kpiChartMeta?.template_title, payload?.kpiChartMeta?.unit);
                        }

                        if (typeof window.renderKpiActualTargetChart === 'function') {
                            window.renderKpiActualTargetChart();
                        } else if (window.kpiActualTargetChartInstance) {
                            // Fallback for older builds
                            const chart = window.kpiActualTargetChartInstance;
                            chart.data.labels = payload.kpiChartData.labels || [];
                            if (chart.data.datasets?.[0]) chart.data.datasets[0].data = payload.kpiChartData.actual || [];
                            if (chart.data.datasets?.[1]) chart.data.datasets[1].data = payload.kpiChartData.target || [];
                            chart.update();
                        }

                        elTableWrapper.innerHTML = buildKpiTableHtml(payload);

                        const elCapaWrapper = document.getElementById('presentation-capa-table');
                        if (elCapaWrapper && typeof payload?.capaTableHtml === 'string') {
                            elCapaWrapper.innerHTML = payload.capaTableHtml;
                        }

                        const elCapaStatusSummary = document.getElementById('presentation-capa-status-summary');
                        if (elCapaStatusSummary && typeof payload?.capaStatusSummaryHtml === 'string') {
                            elCapaStatusSummary.innerHTML = payload.capaStatusSummaryHtml;
                        }

                        if (typeof payload?.selectedCapaStatus !== 'undefined') {
                            selectedCapaStatus = payload.selectedCapaStatus || null;
                        }
                    };

                    const fetchAndApply = async (options = {}) => {
                        if (inFlight) return;
                        inFlight = true;
                        try {
                            const params = new URLSearchParams();
                            if (elDept) params.set('department', elDept.value);
                            if (elMonth.value) params.set('month', elMonth.value);
                            if (!options.omitKpi && elKpi.value) params.set('kpi_definition_id', elKpi.value);
                            if (selectedCapaStatus) params.set('capa_status', selectedCapaStatus);

                            const res = await fetch(`${payloadUrl}?${params.toString()}`, {
                                headers: {
                                    'Accept': 'application/json',
                                },
                            });
                            if (!res.ok) throw new Error(`HTTP ${res.status}`);
                            const payload = await res.json();
                            applyPayload(payload);
                        } catch (e) {
                            // fallback: do nothing; user can still reload manually
                            // eslint-disable-next-line no-console
                            console.error('Failed to load dashboard payload', e);
                        } finally {
                            inFlight = false;
                        }
                    };

                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('#presentation-capa-status-summary .capa-status-filter');
                        if (!button) return;

                        event.preventDefault();
                        const nextStatus = button.dataset.capaStatus || null;
                        selectedCapaStatus = (!nextStatus || selectedCapaStatus === nextStatus) ? null : nextStatus;
                        fetchAndApply();
                    });

                    const moveKpi = (delta) => {
                        const selected = toIntOrNull(elKpi.value);
                        const idx = currentKpis.findIndex((k) => Number(k.id) === Number(selected));
                        if (idx === -1 || currentKpis.length === 0) return;

                        let nextIdx = idx + delta;
                        if (nextIdx < 0) nextIdx = currentKpis.length - 1;
                        if (nextIdx >= currentKpis.length) nextIdx = 0;

                        elKpi.value = String(currentKpis[nextIdx].id);
                        fetchAndApply();
                    };

                    btnPrev.addEventListener('click', () => moveKpi(-1));
                    btnNext.addEventListener('click', () => moveKpi(1));
                    elKpi.addEventListener('change', fetchAndApply);
                    elMonth.addEventListener('change', fetchAndApply);
                    if (elDept) elDept.addEventListener('change', () => fetchAndApply({ omitKpi: true }));

                    document.addEventListener('keydown', (e) => {
                        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                        if (tag === 'input' || tag === 'select' || tag === 'textarea') return;
                        if (e.key === 'ArrowLeft') moveKpi(-1);
                        if (e.key === 'ArrowRight') moveKpi(1);
                    });
                })();
            </script>

            <!-- CAPA Problems Table -->
            <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex flex-col items-center gap-3 text-center">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-100">Recent CAPA Problems</h2>
                    <div id="presentation-capa-status-summary" class="w-full flex justify-center">
                        @include('pages.dashboard.partials.capa-status-summary', [
                            'capaStatusCounts' => $capaStatusCounts ?? [],
                            'selectedCapaStatus' => $selectedCapaStatus ?? null,
                        ])
                    </div>
                </header>
                <div id="presentation-capa-table">
                    @include('pages.dashboard.partials.capa-problems-table', ['capaProblems' => $capaProblems])
                </div>
            </div>
        </div>
    </div>
</x-fullscreen-layout>