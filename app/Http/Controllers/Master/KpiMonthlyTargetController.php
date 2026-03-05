<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\KpiDefinition;
use App\Models\KpiTemplate;
use App\Models\KpiMonthlyTarget;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KpiMonthlyTargetController extends Controller
{
    /**
     * Display yearly targets for a template.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi templates');

        $year = $request->input('year', date('Y'));
        $departmentId = $request->input('department');
        if (!$departmentId) {
            $departmentId = Department::active()->orderBy('name')->value('id');
        }

        $query = KpiMonthlyTarget::with(['kpiDefinition.template', 'department', 'creator'])
            ->where('target_year', $year);

        // Yearly targets are stored as a single row per year (target_month = 1)
        $query->where('target_month', 1);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $targets = $query->orderBy('kpi_definition_id')
            ->paginate(20);

        $departments = Department::active()->orderBy('name')->get();
        $kpis = KpiDefinition::query()
            ->where('department_id', (int) $departmentId)
            ->where('is_active', 1)
            ->with(['template'])
            ->orderByRaw('COALESCE(sort_order, 999999) asc')
            ->orderBy('id')
            ->get();

        return view('master.kpi-monthly-targets.index', compact('targets', 'departments', 'kpis', 'year', 'departmentId'));
    }

    /**
     * Show form to set a yearly target for a template.
     */
    public function create(Request $request)
    {
        Gate::authorize('create kpi templates');

        $kpiDefinitionId = $request->input('kpi_definition_id');
        $year = $request->input('year', date('Y'));

        $kpiDefinition = $kpiDefinitionId ? KpiDefinition::with('template')->findOrFail($kpiDefinitionId) : null;
        $departmentId = $request->input('department');
        if (!$departmentId) {
            $departmentId = Department::active()->orderBy('name')->value('id');
        }

        $kpis = KpiDefinition::query()
            ->where('department_id', (int) $departmentId)
            ->where('is_active', 1)
            ->with(['template'])
            ->orderByRaw('COALESCE(sort_order, 999999) asc')
            ->orderBy('id')
            ->get();

        // Get existing target for this template and year
        $existingTargetValue = null;
        $existingTargetOperator = 'gte';
        $existingTargetUnit = '%';
        if ($kpiDefinition && $departmentId) {
            if ((int) $kpiDefinition->department_id !== (int) $departmentId) {
                $kpiDefinition = null;
            }
        }

        if ($kpiDefinition && $departmentId) {
            $existingTarget = KpiMonthlyTarget::query()
                ->where('kpi_definition_id', $kpiDefinition->id)
                ->where('target_year', $year)
                ->where('target_month', 1)
                ->first(['target_value', 'target_operator', 'target_unit']);

            if ($existingTarget) {
                $existingTargetValue = $existingTarget->target_value;
                $existingTargetOperator = $existingTarget->target_operator ?: 'gte';
                $existingTargetUnit = $existingTarget->target_unit ?: '%';
            }
        }

        return view('master.kpi-monthly-targets.create', compact('kpiDefinition', 'kpis', 'year', 'existingTargetValue', 'existingTargetOperator', 'existingTargetUnit', 'departmentId'));
    }

    /**
     * Store a yearly target.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi templates');

        $validated = $request->validate([
            'kpi_definition_id' => 'required|exists:kpi_template_departments,id',
            'department_id' => 'required|exists:departments,id',
            'target_year' => 'required|integer|min:2020|max:2050',
            'target_unit' => 'required|string|max:20',
            'target_value' => 'required|numeric|min:0',
            'target_operator' => 'required|in:gte,lte',
        ]);

        $kpiDefinition = KpiDefinition::with('template')->findOrFail((int) $validated['kpi_definition_id']);
        if ((int) $kpiDefinition->department_id !== (int) $validated['department_id']) {
            return back()->withInput()->withErrors([
                'kpi_definition_id' => 'Selected KPI does not belong to the selected department.'
            ]);
        }

        if (!$kpiDefinition->is_active || !$kpiDefinition->template || !$kpiDefinition->template->is_active) {
            return back()->withInput()->withErrors([
                'kpi_definition_id' => 'Selected KPI is inactive or missing its template.'
            ]);
        }

        $templateId = (int) $kpiDefinition->kpi_template_id;
        $departmentId = (int) $kpiDefinition->department_id;

        KpiMonthlyTarget::updateOrCreate(
            [
                'kpi_definition_id' => (int) $validated['kpi_definition_id'],
                'target_year' => $validated['target_year'],
                'target_month' => 1,
            ],
            [
                'kpi_template_id' => $templateId,
                'department_id' => $departmentId,
                'target_value' => $validated['target_value'],
                'target_operator' => $validated['target_operator'],
                'target_unit' => $validated['target_unit'],
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('master.kpi-monthly-targets.index', [
            'year' => $validated['target_year'],
            'department' => $departmentId,
        ])->with('success', 'Saved yearly target successfully.');
    }

    /**
     * Show form to edit a yearly target.
     */
    public function edit(KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('edit kpi templates');

        $kpiMonthlyTarget->load(['kpiDefinition.template', 'department']);

        return view('master.kpi-monthly-targets.edit', compact('kpiMonthlyTarget'));
    }

    /**
     * Update a yearly target.
     */
    public function update(Request $request, KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('edit kpi templates');

        $validated = $request->validate([
            'target_unit' => 'required|string|max:20',
            'target_value' => 'required|numeric|min:0',
            'target_operator' => 'required|in:gte,lte',
            'notes' => 'nullable|string',
        ]);

        $kpiMonthlyTarget->update($validated);

        return redirect()->route('master.kpi-monthly-targets.index', [
            'year' => $kpiMonthlyTarget->target_year,
            'department' => $kpiMonthlyTarget->department_id,
        ])->with('success', 'Yearly target updated successfully.');
    }

    /**
     * Delete a yearly target.
     */
    public function destroy(KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('delete kpi templates');

        $year = $kpiMonthlyTarget->target_year;
        $departmentId = $kpiMonthlyTarget->department_id;
        $kpiMonthlyTarget->delete();

        return redirect()->route('master.kpi-monthly-targets.index', ['year' => $year, 'department' => $departmentId])
            ->with('success', 'Yearly target deleted successfully.');
    }

    /**
     * API: Get target for a template and year.
     */
    public function getTarget(Request $request)
    {
        $kpiDefinitionId = $request->input('kpi_definition_id');
        $templateId = $request->input('template_id');
        $departmentId = $request->input('department_id');
        $date = $request->input('date', date('Y-m-d'));

        if (!$kpiDefinitionId && (!$templateId || !$departmentId)) {
            return response()->json([
                'target' => null,
                'has_target' => false,
            ]);
        }

        $year = (int) \Carbon\Carbon::parse($date)->year;

        $query = KpiMonthlyTarget::query()
            ->where('target_year', $year)
            ->where('target_month', 1);

        if ($kpiDefinitionId) {
            $query->where('kpi_definition_id', (int) $kpiDefinitionId);
        } else {
            $query->where('kpi_template_id', (int) $templateId)
                ->where('department_id', (int) $departmentId);
        }

        $row = $query->first(['target_value', 'target_operator', 'target_unit']);

        return response()->json([
            'target' => $row?->target_value !== null ? (float) $row->target_value : null,
            'has_target' => (bool) $row,
            'target_operator' => $row?->target_operator ?: 'gte',
            'target_unit' => $row?->target_unit ?: '%',
        ]);
    }
}
