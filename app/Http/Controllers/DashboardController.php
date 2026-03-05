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

        return response()->json([
            'selectedDepartmentId' => $data['selectedDepartmentId'] ?? null,
            'selectedKpiDefinitionId' => $data['selectedKpiDefinitionId'] ?? null,
            'selectedMonth' => $data['selectedMonth'] ?? null,
            'selectedMonthLabel' => $data['selectedMonthLabel'] ?? null,
            'kpis' => $kpiOptions,
            'kpiChartData' => $chartDataArray,
            'kpiChartMeta' => $data['kpiChartMeta'] ?? [],
            'kpiSeriesLabels' => $data['kpiSeriesLabels'] ?? [],
        ]);
    }

    private function buildDashboardData(Request $request): array
    {
        $user = auth()->user();

        $isPresentation = $request->boolean('fullscreen') || $request->expectsJson();

        $selectedDepartmentId = $request->input('department');
        $selectedKpiDefinitionId = $request->input('kpi_definition_id');
        $selectedTemplateId = $request->input('template'); // legacy (kept; KPI selection is preferred)

        // Dashboard is intentionally cross-department readable for all authenticated users.
        $departments = Department::active()->orderBy('name')->get();

        if ($isPresentation && $user->can_access_all_departments && !$selectedDepartmentId) {
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

            if ($isPresentation && !$selectedKpiDefinitionId && $kpis->isNotEmpty()) {
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
            $templateFields = $selectedTemplate->fields()->orderBy('sort_order')->get();

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
                        $fieldValues[] = $fieldsForDate[$field->field_key] ?? null;
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
            'unit' => $targetUnit,
            'target_operator' => $targetOperator,
            'month_label' => $selectedMonthLabel,
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
