<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\KpiTemplate;
use App\Models\KpiMonthlyTarget;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KpiMonthlyTargetController extends Controller
{
    /**
     * Display monthly targets for a template.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi templates');

        $year = $request->input('year', date('Y'));
        $departmentId = $request->input('department');

        $query = KpiMonthlyTarget::with(['template.department', 'creator'])
            ->where('target_year', $year);

        if ($departmentId) {
            $query->whereHas('template', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $targets = $query->orderBy('target_month')
            ->orderBy('kpi_template_id')
            ->paginate(20);

        $departments = Department::active()->orderBy('name')->get();
        $templates = KpiTemplate::active()->with('department')->orderBy('name')->get();

        return view('master.kpi-monthly-targets.index', compact('targets', 'departments', 'templates', 'year'));
    }

    /**
     * Show form to set monthly targets for a template.
     */
    public function create(Request $request)
    {
        Gate::authorize('create kpi templates');

        $templateId = $request->input('template_id');
        $year = $request->input('year', date('Y'));

        $template = $templateId ? KpiTemplate::findOrFail($templateId) : null;
        $templates = KpiTemplate::active()->with('department')->orderBy('name')->get();

        // Get existing targets for this template and year
        $existingTargets = [];
        if ($template) {
            $existingTargets = KpiMonthlyTarget::where('kpi_template_id', $template->id)
                ->where('target_year', $year)
                ->pluck('target_value', 'target_month')
                ->toArray();
        }

        return view('master.kpi-monthly-targets.create', compact('template', 'templates', 'year', 'existingTargets'));
    }

    /**
     * Store monthly targets.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi templates');

        $validated = $request->validate([
            'kpi_template_id' => 'required|exists:kpi_templates,id',
            'target_year' => 'required|integer|min:2020|max:2050',
            'targets' => 'required|array',
            'targets.*' => 'nullable|numeric|min:0',
        ]);

        $saved = 0;
        foreach ($validated['targets'] as $month => $targetValue) {
            if ($targetValue !== null && $targetValue !== '') {
                KpiMonthlyTarget::updateOrCreate(
                    [
                        'kpi_template_id' => $validated['kpi_template_id'],
                        'target_year' => $validated['target_year'],
                        'target_month' => $month,
                    ],
                    [
                        'target_value' => $targetValue,
                        'created_by' => auth()->id(),
                    ]
                );
                $saved++;
            }
        }

        return redirect()->route('master.kpi-monthly-targets.index', [
            'year' => $validated['target_year']
        ])->with('success', "Saved {$saved} monthly targets successfully.");
    }

    /**
     * Show form to edit a monthly target.
     */
    public function edit(KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('edit kpi templates');

        $kpiMonthlyTarget->load('template');

        return view('master.kpi-monthly-targets.edit', compact('kpiMonthlyTarget'));
    }

    /**
     * Update a monthly target.
     */
    public function update(Request $request, KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('edit kpi templates');

        $validated = $request->validate([
            'target_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $kpiMonthlyTarget->update($validated);

        return redirect()->route('master.kpi-monthly-targets.index', [
            'year' => $kpiMonthlyTarget->target_year
        ])->with('success', 'Monthly target updated successfully.');
    }

    /**
     * Delete a monthly target.
     */
    public function destroy(KpiMonthlyTarget $kpiMonthlyTarget)
    {
        Gate::authorize('delete kpi templates');

        $year = $kpiMonthlyTarget->target_year;
        $kpiMonthlyTarget->delete();

        return redirect()->route('master.kpi-monthly-targets.index', ['year' => $year])
            ->with('success', 'Monthly target deleted successfully.');
    }

    /**
     * API: Get target for a template and date.
     */
    public function getTarget(Request $request)
    {
        $templateId = $request->input('template_id');
        $date = $request->input('date', date('Y-m-d'));

        $target = KpiMonthlyTarget::getTargetForDate($templateId, $date);

        return response()->json([
            'target' => $target,
            'has_target' => $target !== null,
        ]);
    }
}
