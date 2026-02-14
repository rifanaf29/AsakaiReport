<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\KpiTemplate;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class KpiTemplateController extends Controller
{
    /**
     * Display a listing of KPI templates.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi templates');

        $query = KpiTemplate::with('department')->withCount('fields');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->orderBy('code')->paginate(15);
        $departments = Department::active()->orderBy('name')->get();

        return view('master.kpi-templates.index', compact('templates', 'departments'));
    }

    /**
     * Show the form for creating a new KPI template.
     */
    public function create()
    {
        Gate::authorize('create kpi templates');

        $departments = Department::active()->orderBy('name')->get();

        return view('master.kpi-templates.create', compact('departments'));
    }

    /**
     * Store a newly created KPI template.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi templates');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:kpi_templates,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'target_unit' => 'required|string|max:20',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:100',
            'fields.*.field_key' => 'required|string|max:50',
            'fields.*.field_type' => 'required|in:text,number,decimal,date,calculated',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_editable' => 'boolean',
            'fields.*.calculation_formula' => 'nullable|string',
            'fields.*.unit' => 'nullable|string|max:20',
            'fields.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $template = KpiTemplate::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'department_id' => $validated['department_id'],
                'target_unit' => $validated['target_unit'],
                'is_active' => $request->has('is_active'),
            ]);

            // Create template fields if provided
            if (!empty($validated['fields'])) {
                foreach ($validated['fields'] as $index => $field) {
                    $template->fields()->create([
                        'field_name' => $field['field_name'],
                        'field_key' => $field['field_key'],
                        'field_type' => $field['field_type'],
                        'is_required' => $field['is_required'] ?? false,
                        'is_editable' => $field['is_editable'] ?? true,
                        'calculation_formula' => $field['calculation_formula'] ?? null,
                        'unit' => $field['unit'] ?? null,
                        'sort_order' => $field['sort_order'] ?? $index + 1,
                    ]);
                }
            }
        });

        return redirect()->route('master.kpi-templates.index')
            ->with('success', 'KPI Template created successfully.');
    }

    /**
     * Display the specified KPI template.
     */
    public function show(KpiTemplate $kpiTemplate)
    {
        Gate::authorize('view kpi templates');

        $kpiTemplate->load(['department', 'fields' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return view('master.kpi-templates.show', compact('kpiTemplate'));
    }

    /**
     * Show the form for editing the specified KPI template.
     */
    public function edit(KpiTemplate $kpiTemplate)
    {
        Gate::authorize('edit kpi templates');

        $kpiTemplate->load(['fields' => function ($query) {
            $query->orderBy('sort_order');
        }]);
        $departments = Department::active()->orderBy('name')->get();

        return view('master.kpi-templates.edit', compact('kpiTemplate', 'departments'));
    }

    /**
     * Update the specified KPI template.
     */
    public function update(Request $request, KpiTemplate $kpiTemplate)
    {
        Gate::authorize('edit kpi templates');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:kpi_templates,code,' . $kpiTemplate->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'target_unit' => 'required|string|max:20',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:100',
            'fields.*.field_key' => 'required|string|max:50',
            'fields.*.field_type' => 'required|in:text,number,decimal,date,calculated',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_editable' => 'boolean',
            'fields.*.calculation_formula' => 'nullable|string',
            'fields.*.unit' => 'nullable|string|max:20',
            'fields.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($validated, $request, $kpiTemplate) {
            $kpiTemplate->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'department_id' => $validated['department_id'],
                'target_unit' => $validated['target_unit'],
                'is_active' => $request->has('is_active'),
            ]);

            // Delete existing fields and recreate
            $kpiTemplate->fields()->delete();

            if (!empty($validated['fields'])) {
                foreach ($validated['fields'] as $index => $field) {
                    $kpiTemplate->fields()->create([
                        'field_name' => $field['field_name'],
                        'field_key' => $field['field_key'],
                        'field_type' => $field['field_type'],
                        'is_required' => $field['is_required'] ?? false,
                        'is_editable' => $field['is_editable'] ?? true,
                        'calculation_formula' => $field['calculation_formula'] ?? null,
                        'unit' => $field['unit'] ?? null,
                        'sort_order' => $field['sort_order'] ?? $index + 1,
                    ]);
                }
            }
        });

        return redirect()->route('master.kpi-templates.index')
            ->with('success', 'KPI Template updated successfully.');
    }

    /**
     * Remove the specified KPI template.
     */
    public function destroy(KpiTemplate $kpiTemplate)
    {
        Gate::authorize('delete kpi templates');

        // Check if template has entries
        if ($kpiTemplate->entries()->count() > 0) {
            return redirect()->route('master.kpi-templates.index')
                ->with('error', 'Cannot delete template with existing KPI entries.');
        }

        $kpiTemplate->delete();

        return redirect()->route('master.kpi-templates.index')
            ->with('success', 'KPI Template deleted successfully.');
    }
}
