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
                            <div id="presentation-subtitle" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $selectedMonthLabel ?? '' }}{{ ($selectedMonthLabel ?? null) ? ' • ' : '' }}{{ $kpiChartMeta['template_title'] ?? 'All KPIs' }}{{ !empty($kpiChartMeta['unit']) ? ' • Unit: ' . $kpiChartMeta['unit'] : '' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
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

                            <select id="presentation-kpi" class="text-sm border-gray-200 rounded-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                @foreach($kpis as $kpi)
                                    <option value="{{ $kpi->id }}" {{ (int) ($selectedKpiDefinitionId ?? 0) === (int) $kpi->id ? 'selected' : '' }}>
                                        {{ $kpi->display_name ?: ($kpi->template?->code ?: 'KPI') }}
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

                    <div id="presentation-kpi-table" class="mt-4 overflow-x-auto">
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
                                            <div><span class="font-semibold">Actual Sum</span>: {{ $actualCount ? $formatKpiCell($actualSum) : '-' }}{{ $unitSuffix }}</div>
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

                    const formatNumber = (value) => {
                        const n = Number(value);
                        if (!Number.isFinite(n)) return '';
                        const formatted = Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(n);
                        return formatted.replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
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
                        const operator = meta.target_operator || 'gte';

                        const target = Array.isArray(chartData.target) ? chartData.target : [];
                        const actual = Array.isArray(chartData.actual) ? chartData.actual : [];

                        const fieldKeys = Object.keys(chartData).filter((k) => k.startsWith('field:'));
                        const seriesOrder = ['target', 'actual', ...fieldKeys, 'status'];

                        const status = labels.map((_, i) => computeStatus(target[i], actual[i], operator));

                        const actualNumeric = actual
                            .map((v) => Number(v))
                            .filter((v) => Number.isFinite(v));
                        const actualSum = actualNumeric.reduce((acc, v) => acc + v, 0);
                        const actualAvg = actualNumeric.length ? (actualSum / actualNumeric.length) : null;
                        const targetValue = target.map((v) => Number(v)).find((v) => Number.isFinite(v));
                        const okCount = status.filter((v) => v === 'OK').length;
                        const ngCount = status.filter((v) => v === 'NG').length;
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
                                        textClass = 'text-white';
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

                            const cells = labels.map((_, idx) => {
                                const v = values[idx];
                                let tdStyle = '';
                                let tdClass = '';
                                let valueTextClass = 'text-gray-800 dark:text-gray-100';

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

                                if (seriesName === 'status') {
                                    const statusText = v === 'OK' ? '<div class="text-center text-xs text-green-700 dark:text-green-300">OK</div>'
                                        : (v === 'NG' ? '<div class="text-center text-xs text-red-700 dark:text-red-300">NG</div>'
                                        : '<div class="text-center text-xs text-gray-400 dark:text-gray-500"></div>');
                                    return `<td class="p-2 whitespace-nowrap ${tdClass}" style="${tdStyle}">${statusText}</td>`;
                                }

                                const isDynamicField = seriesName.startsWith('field:');
                                const displayValue = isDynamicField ? (v ?? '') : formatNumber(v);
                                return `<td class="p-2 whitespace-nowrap ${tdClass}" style="${tdStyle}"><div class="text-center ${valueTextClass}">${escapeHtml(displayValue)}</div></td>`;
                            }).join('');

                            return `
                                <tr>
                                    <td class="p-2"><div class="text-gray-800 dark:text-gray-100">${escapeHtml(label)}</div></td>
                                    ${cells}
                                </tr>
                            `;
                        };

                        const bodyRows = seriesOrder.map(buildRow).join('');

                        const summaryTarget = Number.isFinite(targetValue) ? `${formatNumber(targetValue)}${unitSuffix}` : `-${unitSuffix}`;
                        const summarySum = actualNumeric.length ? `${formatNumber(actualSum)}${unitSuffix}` : `-${unitSuffix}`;
                        const summaryAvg = actualNumeric.length && Number.isFinite(actualAvg) ? `${formatNumber(actualAvg)}${unitSuffix}` : `-${unitSuffix}`;

                        return `
                            <table class="table-auto w-full text-xs dark:text-gray-300">
                                <thead class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="p-2"><div class="font-semibold text-left">Field</div></th>
                                        ${headerCells}
                                    </tr>
                                </thead>
                                <tbody class="text-xs font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                    ${bodyRows}
                                </tbody>
                                <tfoot class="text-[11px] uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <td class="p-2"><div class="font-semibold text-left">Summary</div></td>
                                        <td class="p-2" colspan="${labels.length}">
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
                        window.kpiActualTargetChartMeta = payload.kpiChartMeta;

                        if (elSubtitle) {
                            elSubtitle.textContent = buildSubtitleText(payload.selectedMonthLabel, payload?.kpiChartMeta?.template_title, payload?.kpiChartMeta?.unit);
                        }

                        if (window.kpiActualTargetChartInstance) {
                            const chart = window.kpiActualTargetChartInstance;
                            chart.data.labels = payload.kpiChartData.labels || [];
                            if (chart.data.datasets?.[0]) chart.data.datasets[0].data = payload.kpiChartData.actual || [];
                            if (chart.data.datasets?.[1]) chart.data.datasets[1].data = payload.kpiChartData.target || [];

                            const unit = payload?.kpiChartMeta?.unit || null;
                            chart.options.scales.y.title.display = Boolean(unit);
                            chart.options.scales.y.title.text = unit ? `Unit: ${unit}` : '';
                            const monthLabel = payload?.kpiChartMeta?.month_label;
                            const title = payload?.kpiChartMeta?.template_title || 'All Templates';
                            chart.options.plugins.title.text = monthLabel ? [title, monthLabel] : title;

                            chart.update();
                        }

                        elTableWrapper.innerHTML = buildKpiTableHtml(payload);
                    };

                    const fetchAndApply = async (options = {}) => {
                        if (inFlight) return;
                        inFlight = true;
                        try {
                            const params = new URLSearchParams();
                            if (elDept) params.set('department', elDept.value);
                            if (elMonth.value) params.set('month', elMonth.value);
                            if (!options.omitKpi && elKpi.value) params.set('kpi_definition_id', elKpi.value);

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
</x-fullscreen-layout>