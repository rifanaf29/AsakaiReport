<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KpiDefinition;
use App\Models\KpiTemplate;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class KpiTemplateAssignmentController extends Controller
{
    /**
     * Display template assignments for a department.
     */
    public function index(Request $request)
    {
        Gate::authorize('edit kpi templates');

        $departments = Department::active()->orderBy('name')->get();
        $selectedDepartmentId = (int) ($request->input('department') ?? ($departments->first()->id ?? 0));

        $templates = KpiTemplate::active()
            ->with([
                'fields' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->withCount('fields')
            ->orderBy('code')
            ->get();

        $templatesPayload = $templates->map(function ($t) {
            return [
                'id' => (int) $t->id,
                'code' => $t->code,
                'fields_count' => (int) ($t->fields_count ?? 0),
                'fields' => $t->fields
                    ? $t->fields
                        ->where('field_type', '!=', 'calculated')
                        ->values()
                        ->map(fn ($f) => [
                            'field_key' => $f->field_key,
                            'field_name' => $f->field_name,
                            'unit' => $f->unit,
                            'field_type' => $f->field_type,
                        ])
                        ->all()
                    : [],
            ];
        })->values();

        $kpis = KpiDefinition::query()
            ->where('department_id', $selectedDepartmentId)
            ->with(['template'])
            ->orderByRaw('COALESCE(sort_order, 999999) asc')
            ->orderBy('id')
            ->get();

        return view('master.kpi-templates.assignments', compact('templates', 'templatesPayload', 'kpis', 'departments', 'selectedDepartmentId'));
    }

    /**
     * Update assignments for a department.
     */
    public function update(Request $request)
    {
        Gate::authorize('edit kpi templates');

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'kpis' => 'nullable|array',
            'kpis.*.id' => 'nullable|integer',
            'kpis.*.kpi_template_id' => 'required|exists:kpi_templates,id',
            'kpis.*.display_name' => 'required|string|max:150',
            'kpis.*.is_active' => 'nullable|boolean',
            'kpis.*.sort_order' => 'nullable|integer|min:0|max:1000000',
            'kpis.*.field_units' => 'nullable|array',
            'kpis.*.field_units.*' => 'nullable|string|max:20',
        ]);

        $departmentId = (int) $validated['department_id'];
        $kpisInput = $validated['kpis'] ?? [];

        try {
            DB::transaction(function () use ($departmentId, $kpisInput) {
                $department = Department::findOrFail($departmentId);

                $existingIds = KpiDefinition::query()
                    ->where('department_id', $departmentId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $keepIds = [];

                foreach ($kpisInput as $row) {
                    $id = isset($row['id']) ? (int) $row['id'] : null;

                    $fieldUnits = [];
                    if (!empty($row['field_units']) && is_array($row['field_units'])) {
                        foreach ($row['field_units'] as $key => $unit) {
                            $key = trim((string) $key);
                            if ($key === '') continue;
                            $unit = trim((string) $unit);
                            if ($unit === '') continue;
                            $fieldUnits[$key] = $unit;
                        }
                    }

                    $payload = [
                        'department_id' => $department->id,
                        'kpi_template_id' => (int) $row['kpi_template_id'],
                        'display_name' => $row['display_name'],
                        'is_active' => !empty($row['is_active']),
                        'sort_order' => isset($row['sort_order']) && $row['sort_order'] !== '' ? (int) $row['sort_order'] : null,
                        'field_units' => !empty($fieldUnits) ? $fieldUnits : null,
                    ];

                    if ($id && in_array($id, $existingIds, true)) {
                        KpiDefinition::query()->where('id', $id)->where('department_id', $departmentId)->update($payload);
                        $keepIds[] = $id;
                    } else {
                        $created = KpiDefinition::create($payload);
                        $keepIds[] = (int) $created->id;
                    }
                }

                // Delete definitions removed from the form
                $deleteIds = array_values(array_diff($existingIds, $keepIds));
                if (!empty($deleteIds)) {
                    KpiDefinition::query()
                        ->where('department_id', $departmentId)
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'kpi_template_departments_kpi_template_id_department_id_unique')) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'kpis' => 'Your database still enforces one template per department. Run `php artisan migrate` to apply the migration that removes this unique constraint, then try again.'
                    ]);
            }

            throw $e;
        }

        return redirect()
            ->route('master.kpi-template-assignments.index', ['department' => $departmentId])
            ->with('success', 'Department KPIs updated successfully.');
    }
}
