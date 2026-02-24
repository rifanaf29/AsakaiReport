<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\KpiEntry;
use App\Models\KpiMonthlyTarget;
use App\Models\KpiTemplate;
use App\Models\CapaProblem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isFullscreen = $request->boolean('fullscreen');

        $selectedDepartmentId = $request->input('department');
        $selectedTemplateId = $request->input('template');

        // Enforce department access for non-all-departments users.
        if (!$user->can_access_all_departments) {
            $selectedDepartmentId = $user->department_id;
        }

        $departments = $user->can_access_all_departments
            ? Department::active()->orderBy('name')->get()
            : collect([$user->department]);

        $templates = KpiTemplate::active()
            ->when($selectedDepartmentId, function ($q) use ($selectedDepartmentId) {
                return $q->where('department_id', $selectedDepartmentId);
            })
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('name')
            ->get();

        $selectedTemplate = null;
        if ($selectedTemplateId) {
            $selectedTemplate = $templates->firstWhere('id', (int) $selectedTemplateId);
            if (!$selectedTemplate) {
                $selectedTemplateId = null;
            }
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
            ->when($selectedTemplateId, function ($q) use ($selectedTemplateId) {
                return $q->where('kpi_template_id', $selectedTemplateId);
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
        if ($selectedTemplateId) {
            $rawTarget = KpiMonthlyTarget::query()
                ->where('kpi_template_id', (int) $selectedTemplateId)
                ->where('target_year', $monthStart->year)
                ->where('target_month', $monthStart->month)
                ->value('target_value');

            $monthlyTargetValue = $rawTarget !== null ? (float) $rawTarget : null;
        } else {
            $templateIds = $templates->pluck('id')->filter()->values();
            if ($templateIds->isNotEmpty()) {
                $targetsForMonth = KpiMonthlyTarget::query()
                    ->whereIn('kpi_template_id', $templateIds)
                    ->where('target_year', $monthStart->year)
                    ->where('target_month', $monthStart->month)
                    ->pluck('target_value');

                $monthlyTargetValue = $targetsForMonth->isNotEmpty()
                    ? (float) $targetsForMonth->sum(fn ($v) => (float) $v)
                    : null;
            }
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

        $kpiChartMeta = [
            'template_title' => $selectedTemplate ? trim(($selectedTemplate->code ? ($selectedTemplate->code . ' - ') : '') . $selectedTemplate->name) : 'All Templates',
            'unit' => $selectedTemplate?->target_unit,
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
            ->whereHas('area.kpiEntry', function ($q) use ($selectedDepartmentId, $selectedTemplateId, $monthStart, $monthEnd) {
                $q->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->when($selectedDepartmentId, function ($inner) use ($selectedDepartmentId) {
                        return $inner->where('department_id', $selectedDepartmentId);
                    })
                    ->when($selectedTemplateId, function ($inner) use ($selectedTemplateId) {
                        return $inner->where('kpi_template_id', $selectedTemplateId);
                    });
            })
            ->latest()
            ->limit(15)
            ->get();

        $viewName = $isFullscreen ? 'pages/dashboard/dashboard-fullscreen' : 'pages/dashboard/dashboard';

        return view($viewName, [
            'departments' => $departments,
            'templates' => $templates,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedTemplateId' => $selectedTemplateId,
            'selectedTemplate' => $selectedTemplate,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
            'kpiChartData' => $kpiChartData,
            'kpiChartMeta' => $kpiChartMeta,
            'capaProblems' => $capaProblems,
        ]);
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
