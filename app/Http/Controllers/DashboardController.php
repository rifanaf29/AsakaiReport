<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\KpiEntry;
use App\Models\KpiMonthlyTarget;
use App\Models\KpiDefinition;
use App\Models\CapaProblem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const TEMPLATE_CNC_WASTE = 'TPL_PD_WASTE_CNC_BENDING';
    private const TEMPLATE_PD_MP_OT = 'TPL_PD_MP_OT';
    public function index(Request $request)
    {
        $data = $this->buildDashboardData($request);
        $viewName = $request->boolean('fullscreen') ? 'pages/dashboard/dashboard-fullscreen' : 'pages/dashboard/dashboard';
        return view($viewName, $data);
    }

    public function payload(Request $request)
    {
        $data = $this->buildDashboardData($request);

        $kpis = $data['kpis'] ?? collect();
        $kpiOptions = $kpis->map(function ($kpi) {
            $name = $kpi->display_name ?: ($kpi->template?->code ?: 'KPI');
            return [
                'id' => (int) $kpi->id,
                'name' => $name,
                'template_code' => $kpi->template?->code,
            ];
        })->values();

        $chartData = $data['kpiChartData'] ?? [];
        $chartDataArray = [];
        foreach ($chartData as $key => $value) {
            if ($value instanceof \Illuminate\Support\Collection) {
                $chartDataArray[$key] = $value->values()->all();
            } else {
                $chartDataArray[$key] = $value;
            }
        }

        $capaTableHtml = view('pages.dashboard.partials.capa-problems-table', [
            'capaProblems' => $data['capaProblems'] ?? collect(),
        ])->render();

        return response()->json([
            'selectedDepartmentId' => $data['selectedDepartmentId'] ?? null,
            'selectedKpiDefinitionId' => $data['selectedKpiDefinitionId'] ?? null,
            'selectedMonth' => $data['selectedMonth'] ?? null,
            'selectedMonthLabel' => $data['selectedMonthLabel'] ?? null,
            'kpis' => $kpiOptions,
            'kpiChartData' => $chartDataArray,
            'kpiChartMeta' => $data['kpiChartMeta'] ?? [],
            'kpiSeriesLabels' => $data['kpiSeriesLabels'] ?? [],
            'capaTableHtml' => $capaTableHtml,
        ]);
    }

    private function buildDashboardData(Request $request): array
    {
        $isPresentation = $request->boolean('fullscreen') || $request->expectsJson();

        $selectedDepartmentId = $request->input('department');
        $selectedKpiDefinitionId = $request->input('kpi_definition_id');
        $selectedTemplateId = $request->input('template'); // legacy (kept; KPI selection is preferred)

        // Dashboard is intentionally cross-department readable for all authenticated users.
        $departments = Department::active()->orderByRaw("FIELD(id, 4, 2, 3, 5, 7, 1, 8,6)")->get();

        if (!$selectedDepartmentId) {
            $selectedDepartmentId = optional($departments->first())->id;
        }

        $kpis = collect();
        if ($selectedDepartmentId) {
            $kpis = KpiDefinition::query()
                ->where('department_id', (int) $selectedDepartmentId)
                ->where('is_active', 1)
                ->with(['template', 'department'])
                ->orderByRaw('COALESCE(sort_order, 999999) asc')
                ->orderBy('id')
                ->get()
                ->filter(fn ($kpi) => (bool) $kpi->template);
        }

        if (!$selectedKpiDefinitionId && $kpis->isNotEmpty()) {
            $selectedKpiDefinitionId = (int) $kpis->first()->id;
        }

        $selectedKpiDefinition = null;
        if ($selectedKpiDefinitionId) {
            $selectedKpiDefinition = $kpis->firstWhere('id', (int) $selectedKpiDefinitionId);

            // If KPI doesn't exist in the current list (e.g. user loaded URL directly), fetch it.
            if (!$selectedKpiDefinition) {
                $selectedKpiDefinition = KpiDefinition::query()
                    ->where('id', (int) $selectedKpiDefinitionId)
                    ->with(['template', 'department'])
                    ->first();
            }

            if ($selectedKpiDefinition) {
                // Enforce access and keep filters consistent.
                $kpiDeptId = (int) $selectedKpiDefinition->department_id;
                $selectedDepartmentId = $kpiDeptId;
            } else {
                $selectedKpiDefinitionId = null;
            }
        }

        $selectedTemplate = $selectedKpiDefinition?->template;
        if ($selectedTemplate) {
            $selectedTemplateId = (int) $selectedTemplate->id;
        }

        // Month filter (YYYY-MM). Default = current month.
        $selectedMonth = $request->input('month', Carbon::today()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Exception $e) {
            $selectedMonth = Carbon::today()->format('Y-m');
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();
        $selectedMonthLabel = $monthStart->format('F Y');

        $kpiSeriesQuery = KpiEntry::query()
            ->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->when($selectedDepartmentId, function ($q) use ($selectedDepartmentId) {
                return $q->where('department_id', $selectedDepartmentId);
            })
            ->when($selectedKpiDefinitionId && Schema::hasColumn('kpi_entries', 'kpi_definition_id'), function ($q) use ($selectedKpiDefinitionId) {
                return $q->where('kpi_definition_id', (int) $selectedKpiDefinitionId);
            })
            ->when(!$selectedKpiDefinitionId && $selectedTemplateId, function ($q) use ($selectedTemplateId) {
                return $q->where('kpi_template_id', (int) $selectedTemplateId);
            });

        $kpiSeries = $kpiSeriesQuery
            ->selectRaw('entry_date, SUM(actual) as actual_sum')
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->get();

        $seriesMap = $kpiSeries->keyBy(function ($row) {
            return Carbon::parse($row->entry_date)->toDateString();
        });

        $labels = [];
        $targets = [];
        $actuals = [];

        $monthlyTargetValue = null;
        if ($selectedKpiDefinitionId && Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id')) {
            $targetQuery = KpiMonthlyTarget::query()
                ->where('kpi_definition_id', (int) $selectedKpiDefinitionId)
                ->where('target_year', $monthStart->year)
                ->where('target_month', 1);

            $rawTarget = $targetQuery->value('target_value');
            $monthlyTargetValue = $rawTarget !== null ? (float) $rawTarget : null;
        } else {
            $monthlyTargetValue = null;
        }

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dateKey = $cursor->toDateString();
            $row = $seriesMap->get($dateKey);
            $labels[] = $cursor->format('m-d-Y');
            $targets[] = $monthlyTargetValue;
            if (!$row || $row->actual_sum === null) {
                $actuals[] = null;
            } else {
                $actuals[] = (float) $row->actual_sum;
            }
            $cursor->addDay();
        }

        $kpiChartData = [
            'labels' => collect($labels),
            'target' => collect($targets),
            'actual' => collect($actuals),
        ];

        $kpiSeriesLabels = [];
        if ($selectedTemplate) {
            $isCncWaste = (string) ($selectedTemplate->code ?? '') === self::TEMPLATE_CNC_WASTE;
            $isPdMpOt = (string) ($selectedTemplate->code ?? '') === self::TEMPLATE_PD_MP_OT;
            $templateFields = $selectedTemplate->fields()->orderBy('sort_order')->get();

            // For CNC Waste dashboard, keep chart special rendering but include all fields for the table.
            if ($isCncWaste) {
                $kpiSeriesLabels['actual'] = 'Total Waste (%)';
            }

            if ($templateFields->isNotEmpty()) {
                $entriesForFields = KpiEntry::query()
                    ->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->where('kpi_template_id', $selectedTemplate->id)
                    ->when($selectedDepartmentId, function ($q) use ($selectedDepartmentId) {
                        return $q->where('department_id', $selectedDepartmentId);
                    })
                    ->when($selectedKpiDefinitionId && Schema::hasColumn('kpi_entries', 'kpi_definition_id'), function ($q) use ($selectedKpiDefinitionId) {
                        return $q->where('kpi_definition_id', (int) $selectedKpiDefinitionId);
                    })
                    ->select(['entry_date', 'dynamic_fields'])
                    ->orderBy('entry_date')
                    ->get();

                $dynamicByDate = [];
                foreach ($entriesForFields as $entry) {
                    $dynamicByDate[Carbon::parse($entry->entry_date)->toDateString()] = $entry->dynamic_fields ?? [];
                }

                foreach ($templateFields as $field) {
                    $seriesKey = 'field:' . $field->field_key;
                    $kpiSeriesLabels[$seriesKey] = $field->field_name;
                    $fieldValues = [];

                    $cursor = $monthStart->copy();
                    while ($cursor->lte($monthEnd)) {
                        $dateKey = $cursor->toDateString();
                        $fieldsForDate = $dynamicByDate[$dateKey] ?? [];
                        $value = $fieldsForDate[$field->field_key] ?? null;

                        if ($isCncWaste && $field->field_key === 'total_waste_kg' && ($value === null || $value === '')) {
                            $cb1 = (isset($fieldsForDate['cb1']) && is_numeric($fieldsForDate['cb1'])) ? (float) $fieldsForDate['cb1'] : 0.0;
                            $cb2 = (isset($fieldsForDate['cb2']) && is_numeric($fieldsForDate['cb2'])) ? (float) $fieldsForDate['cb2'] : 0.0;
                            $cb3 = (isset($fieldsForDate['cb3']) && is_numeric($fieldsForDate['cb3'])) ? (float) $fieldsForDate['cb3'] : 0.0;
                            $cb4 = (isset($fieldsForDate['cb4']) && is_numeric($fieldsForDate['cb4'])) ? (float) $fieldsForDate['cb4'] : 0.0;
                            $sum = $cb1 + $cb2 + $cb3 + $cb4;
                            $value = $sum !== 0.0 ? $sum : null;
                        }

                        if ($isPdMpOt && $field->field_key === 'total_ot_charge' && ($value === null || $value === '')) {
                            $c1 = (isset($fieldsForDate['ot_charge_pd1']) && is_numeric($fieldsForDate['ot_charge_pd1'])) ? (float) $fieldsForDate['ot_charge_pd1'] : 0.0;
                            $c2 = (isset($fieldsForDate['ot_charge_pd2']) && is_numeric($fieldsForDate['ot_charge_pd2'])) ? (float) $fieldsForDate['ot_charge_pd2'] : 0.0;
                            $c3 = (isset($fieldsForDate['ot_charge_pd3']) && is_numeric($fieldsForDate['ot_charge_pd3'])) ? (float) $fieldsForDate['ot_charge_pd3'] : 0.0;
                            $c4 = (isset($fieldsForDate['ot_charge_pd4']) && is_numeric($fieldsForDate['ot_charge_pd4'])) ? (float) $fieldsForDate['ot_charge_pd4'] : 0.0;
                            $c5 = (isset($fieldsForDate['ot_charge_pd5']) && is_numeric($fieldsForDate['ot_charge_pd5'])) ? (float) $fieldsForDate['ot_charge_pd5'] : 0.0;
                            $sum = $c1 + $c2 + $c3 + $c4 + $c5;
                            $value = $sum !== 0.0 ? $sum : null;
                        }

                        $fieldValues[] = $value;
                        $cursor->addDay();
                    }

                    $kpiChartData[$seriesKey] = collect($fieldValues);
                }
            }
        }

        $targetOperator = 'gte';
        $targetUnit = null;
        if ($selectedKpiDefinitionId && Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id')) {
            $targetBaseQuery = KpiMonthlyTarget::query()
                ->where('kpi_definition_id', (int) $selectedKpiDefinitionId)
                ->where('target_year', $monthStart->year)
                ->where('target_month', 1);

            $targetOperator = (clone $targetBaseQuery)->value('target_operator') ?: 'gte';
            $targetUnit = (clone $targetBaseQuery)->value('target_unit');
        }

        $kpiTitle = 'All KPIs';
        if ($selectedKpiDefinition) {
            $kpiTitle = trim((string) ($selectedKpiDefinition->display_name ?: ($selectedKpiDefinition->template?->code ?: 'KPI')));
        }

        $kpiChartMeta = [
            'template_title' => $kpiTitle,
            'template_code' => $selectedTemplate?->code,
            'unit' => $targetUnit,
            'target_operator' => $targetOperator,
            'month_label' => $selectedMonthLabel,
            'target_mode' => $selectedTemplate?->target_mode ?? 'with_target',
            'dashboard_fields' => $selectedTemplate?->dashboard_fields ?? [],
        ];

        // CAPA table: recent problems linked to KPI entries in the current filters.
        $capaProblems = CapaProblem::query()
            ->with([
                'area',
                'area.kpiEntry',
                'causes',
                'causes.actionPlans',
                'causes.actionPlans.pic',
                'actionPlans',
                'actionPlans.pic',
                'actionPlans.cause',
            ])
            ->whereHas('area.kpiEntry', function ($q) use ($selectedDepartmentId, $selectedTemplateId, $selectedKpiDefinitionId, $monthStart, $monthEnd) {
                $q->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->when($selectedDepartmentId, function ($inner) use ($selectedDepartmentId) {
                        return $inner->where('department_id', $selectedDepartmentId);
                    })
                    ->when($selectedKpiDefinitionId && Schema::hasColumn('kpi_entries', 'kpi_definition_id'), function ($inner) use ($selectedKpiDefinitionId) {
                        return $inner->where('kpi_definition_id', (int) $selectedKpiDefinitionId);
                    })
                    ->when(!$selectedKpiDefinitionId && $selectedTemplateId, function ($inner) use ($selectedTemplateId) {
                        return $inner->where('kpi_template_id', (int) $selectedTemplateId);
                    });
            })
            ->latest()
            ->limit(15)
            ->get();

        return [
            'departments' => $departments,
            'kpis' => $kpis,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedKpiDefinitionId' => $selectedKpiDefinitionId,
            'selectedKpiDefinition' => $selectedKpiDefinition,
            'selectedTemplateId' => $selectedTemplateId,
            'selectedTemplate' => $selectedTemplate,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
            'kpiChartData' => $kpiChartData,
            'kpiChartMeta' => $kpiChartMeta,
            'kpiSeriesLabels' => $kpiSeriesLabels,
            'capaProblems' => $capaProblems,
        ];
    }

    /**
     * Displays the analytics screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function analytics()
    {
        return view('pages/dashboard/analytics');
    }

    /**
     * Displays the fintech screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function fintech()
    {
        return view('pages/dashboard/fintech');
    }
}
