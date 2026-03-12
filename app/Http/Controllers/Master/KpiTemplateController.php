<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\KpiTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KpiTemplateController extends Controller
{
    /**
     * Display a listing of KPI templates.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi templates');

        $query = KpiTemplate::with(['departments'])->withCount('fields');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->orderBy('code')->paginate(15);
        return view('master.kpi-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new KPI template.
     */
    public function create()
    {
        Gate::authorize('create kpi templates');

        return view('master.kpi-templates.create');
    }

    /**
     * Store a newly created KPI template.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi templates');

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:kpi_templates,code',
            'description' => 'nullable|string',
            'actual_mode' => 'required|in:manual,aggregated',
            'actual_aggregation' => 'required_if:actual_mode,aggregated|nullable|in:sum,avg,min,max,formula',
            'actual_field_keys' => 'nullable|array',
            'actual_field_keys.*' => 'string|max:50',
            'actual_formula' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:100',
            'fields.*.field_key' => 'required|string|max:50',
            'fields.*.field_type' => 'required|in:text,number,decimal,accounting,date,calculated',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_editable' => 'boolean',
            'fields.*.calculation_formula' => 'nullable|string',
            'fields.*.unit' => 'nullable|string|max:20',
            'fields.*.sort_order' => 'nullable|integer',
        ]);

        $validator->after(function ($validator) use ($request) {
            $mode = $request->input('actual_mode');
            if ($mode !== 'aggregated') return;

            $aggregation = $request->input('actual_aggregation');
            if ($aggregation === 'formula') {
                if (!trim((string) $request->input('actual_formula'))) {
                    $validator->errors()->add('actual_formula', 'Actual formula is required when aggregation is set to Formula.');
                }
                return;
            }

            $keys = $request->input('actual_field_keys');
            if (!is_array($keys) || count($keys) < 1) {
                $validator->errors()->add('actual_field_keys', 'Please select at least one source field.');
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($validated, $request) {
            $template = KpiTemplate::create([
                'code' => $validated['code'],
                'name' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'actual_mode' => $validated['actual_mode'],
                'actual_aggregation' => $validated['actual_aggregation'] ?? null,
                'actual_field_keys' => ($validated['actual_aggregation'] ?? null) === 'formula' ? null : ($validated['actual_field_keys'] ?? null),
                'actual_formula' => ($validated['actual_aggregation'] ?? null) === 'formula' ? ($validated['actual_formula'] ?? null) : null,
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

        $kpiTemplate->load(['departments', 'fields' => function ($query) {
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
        }, 'departments']);

        return view('master.kpi-templates.edit', compact('kpiTemplate'));
    }

    /**
     * Update the specified KPI template.
     */
    public function update(Request $request, KpiTemplate $kpiTemplate)
    {
        Gate::authorize('edit kpi templates');

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:kpi_templates,code,' . $kpiTemplate->id,
            'description' => 'nullable|string',
            'actual_mode' => 'required|in:manual,aggregated',
            'actual_aggregation' => 'required_if:actual_mode,aggregated|nullable|in:sum,avg,min,max,formula',
            'actual_field_keys' => 'nullable|array',
            'actual_field_keys.*' => 'string|max:50',
            'actual_formula' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:100',
            'fields.*.field_key' => 'required|string|max:50',
            'fields.*.field_type' => 'required|in:text,number,decimal,accounting,date,calculated',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_editable' => 'boolean',
            'fields.*.calculation_formula' => 'nullable|string',
            'fields.*.unit' => 'nullable|string|max:20',
            'fields.*.sort_order' => 'nullable|integer',
        ]);

        $validator->after(function ($validator) use ($request) {
            $mode = $request->input('actual_mode');
            if ($mode !== 'aggregated') return;

            $aggregation = $request->input('actual_aggregation');
            if ($aggregation === 'formula') {
                if (!trim((string) $request->input('actual_formula'))) {
                    $validator->errors()->add('actual_formula', 'Actual formula is required when aggregation is set to Formula.');
                }
                return;
            }

            $keys = $request->input('actual_field_keys');
            if (!is_array($keys) || count($keys) < 1) {
                $validator->errors()->add('actual_field_keys', 'Please select at least one source field.');
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($validated, $request, $kpiTemplate) {
            $kpiTemplate->update([
                'code' => $validated['code'],
                'name' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'actual_mode' => $validated['actual_mode'],
                'actual_aggregation' => $validated['actual_aggregation'] ?? null,
                'actual_field_keys' => ($validated['actual_aggregation'] ?? null) === 'formula' ? null : ($validated['actual_field_keys'] ?? null),
                'actual_formula' => ($validated['actual_aggregation'] ?? null) === 'formula' ? ($validated['actual_formula'] ?? null) : null,
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
