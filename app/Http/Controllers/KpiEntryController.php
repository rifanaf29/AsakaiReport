<?php

namespace App\Http\Controllers;

use App\Models\KpiEntry;
use App\Models\KpiTemplate;
use App\Models\Department;
use App\Models\CapaArea;
use App\Models\CapaProblem;
use App\Models\CapaCause;
use App\Models\CapaActionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class KpiEntryController extends Controller
{
    /**
     * Display a listing of KPI entries.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi');

        $user = auth()->user();
        $query = KpiEntry::with(['template', 'department'])
            ->withCount([
                'capaProblems as problems_count',
                'capaProblems as problems_closed_count' => function ($problemQuery) {
                    // A "Closed" problem = has at least one action plan and all action plans are closed.
                    $problemQuery
                        ->whereExists(function ($subQuery) {
                            $subQuery->selectRaw('1')
                                ->from('capa_causes')
                                ->join('capa_action_plans', 'capa_action_plans.capa_cause_id', '=', 'capa_causes.id')
                                ->whereColumn('capa_causes.capa_problem_id', 'capa_problems.id')
                                ->whereNull('capa_causes.deleted_at')
                                ->whereNull('capa_action_plans.deleted_at');
                        })
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->selectRaw('1')
                                ->from('capa_causes')
                                ->join('capa_action_plans', 'capa_action_plans.capa_cause_id', '=', 'capa_causes.id')
                                ->whereColumn('capa_causes.capa_problem_id', 'capa_problems.id')
                                ->whereNull('capa_causes.deleted_at')
                                ->whereNull('capa_action_plans.deleted_at')
                                ->where('capa_action_plans.status', '!=', 'close');
                        });
                },
            ]);

        // Department filter based on user access
        if (!$user->can_access_all_departments) {
            $query->where('department_id', $user->department_id);
        } elseif ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Template filter
        if ($request->filled('template')) {
            $query->where('kpi_template_id', $request->template);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $entries = $query->latest('entry_date')->paginate(15);
        $departments = $user->can_access_all_departments 
            ? Department::active()->orderBy('name')->get() 
            : collect([$user->department]);
        $templates = KpiTemplate::active()
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('name')->get();

        return view('kpi.entries.index', compact('entries', 'departments', 'templates'));
    }

    /**
     * Show the form for creating a new KPI entry.
     */
    public function create(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();
        $templates = KpiTemplate::active()
            ->with(['fields' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('name')->get();

        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = $templates->firstWhere('id', $request->template_id);
        }

        // Get existing CAPA area names for dropdown
        $capaAreas = CapaArea::select('area_name')
            ->distinct()
            ->orderBy('area_name')
            ->pluck('area_name');

        return view('kpi.entries.create', compact('templates', 'selectedTemplate', 'capaAreas'));
    }

    /**
     * Store a newly created KPI entry.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi');

        $validated = $request->validate([
            'kpi_template_id' => 'required|exists:kpi_templates,id',
            'entry_date' => 'required|date',
            'target' => 'required|numeric',
            'actual' => 'required|numeric',
            'notes' => 'nullable|string',
            'dynamic_fields' => 'nullable|array',
            // CAPA areas (array of areas, each with their own problems)
            'capa_areas' => 'nullable|array',
            'capa_areas.*.capa_date' => 'nullable|date',
            'capa_areas.*.area_name' => 'nullable|string',
            'capa_areas.*.area_description' => 'nullable|string',
            'capa_areas.*.problems' => 'nullable|array',
            'capa_areas.*.problems.*.problem_description' => 'nullable|string',
            'capa_areas.*.problems.*.severity' => 'nullable|in:low,medium,high,critical',
            'capa_areas.*.problems.*.causes' => 'nullable|array',
            'capa_areas.*.problems.*.causes.*.cause_description' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans' => 'nullable|array',
            'capa_areas.*.problems.*.causes.*.action_plans.*.description' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.person_in_charge' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.due_date' => 'nullable|date',
            'capa_areas.*.problems.*.causes.*.action_plans.*.keterangan' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.status' => 'nullable|in:open,progress,close',
        ]);

        // Get template and verify department access
        $template = KpiTemplate::findOrFail($validated['kpi_template_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($template->department_id)) {
            abort(403, 'You do not have access to this department.');
        }

        // Auto-calculate status
        $status = 'OK';
        if ($validated['actual'] < $validated['target']) {
            $status = 'NG';
        }

        // Check if CAPA data exists
        $hasCapaData = false;
        if (!empty($validated['capa_areas'])) {
            foreach ($validated['capa_areas'] as $areaData) {
                if (!empty($areaData['problems']) && count(array_filter($areaData['problems'], function($problem) {
                    return !empty($problem['problem_description']);
                })) > 0) {
                    $hasCapaData = true;
                    break;
                }
            }
        }

        // Check if CAPA is required but not filled
        if ($status === 'NG' && !$hasCapaData) {
            return back()->withInput()->withErrors([
                'capa' => 'CAPA is required when KPI status is NG. Please add at least one problem.'
            ]);
        }

        DB::beginTransaction();
        try {
            // Create KPI Entry
            $entry = KpiEntry::create([
                'kpi_template_id' => $validated['kpi_template_id'],
                'department_id' => $template->department_id,
                'entry_date' => $validated['entry_date'],
                'target' => $validated['target'],
                'actual' => $validated['actual'],
                'status' => $status,
                'notes' => $validated['notes'],
                'dynamic_fields' => $validated['dynamic_fields'] ?? null,
                'created_by' => $user->id,
            ]);

            // Create CAPA if data is provided
            if ($hasCapaData && !empty($validated['capa_areas'])) {
                // Loop through each CAPA area
                foreach ($validated['capa_areas'] as $areaIndex => $areaData) {
                    // Skip if no area name or no problems
                    if (empty($areaData['area_name']) || empty($areaData['problems'])) {
                        continue;
                    }

                    // Check if this area has any valid problems
                    $validProblems = array_filter($areaData['problems'], function($problem) {
                        return !empty($problem['problem_description']);
                    });
                    
                    if (empty($validProblems)) {
                        continue; // Skip areas with no valid problems
                    }

                    // Create CAPA Area
                    $capaArea = CapaArea::create([
                        'department_id' => $template->department_id,
                        'kpi_entry_id' => $entry->id,
                        'capa_date' => $areaData['capa_date'] ?? $validated['entry_date'],
                        'area_name' => $areaData['area_name'],
                        'area_description' => $areaData['area_description'] ?? null,
                        'is_mandatory' => $status === 'NG',
                        'created_by' => $user->id,
                    ]);

                    // Loop through problems in this area
                    foreach ($areaData['problems'] as $problemIndex => $problemData) {
                        if (empty($problemData['problem_description'])) {
                            continue; // Skip empty problems
                        }

                        // Create CAPA Problem
                        $capaProblem = CapaProblem::create([
                            'capa_area_id' => $capaArea->id,
                            'problem_description' => $problemData['problem_description'],
                            'severity' => $problemData['severity'] ?? 'medium',
                            'created_by' => $user->id,
                        ]);

                        // Loop through causes for this problem
                        if (!empty($problemData['causes'])) {
                            foreach ($problemData['causes'] as $causeData) {
                                if (empty($causeData['cause_description'])) {
                                    continue; // Skip empty causes
                                }

                                // Create CAPA Cause
                                $capaCause = CapaCause::create([
                                    'capa_problem_id' => $capaProblem->id,
                                    'cause_description' => $causeData['cause_description'],
                                    'created_by' => $user->id,
                                ]);

                                // Loop through action plans for this cause
                                if (!empty($causeData['action_plans'])) {
                                    foreach ($causeData['action_plans'] as $actionData) {
                                        if (empty($actionData['description'])) {
                                            continue; // Skip empty actions
                                        }

                                        // Create CAPA Action Plan
                                        CapaActionPlan::create([
                                            'capa_cause_id' => $capaCause->id,
                                            'description' => $actionData['description'],
                                            'person_in_charge' => $actionData['person_in_charge'] ?? $user->name,
                                            'due_date' => $actionData['due_date'] ?? now()->addDays(30),
                                            'keterangan' => $actionData['keterangan'] ?? null,
                                            'status' => $actionData['status'] ?? 'open',
                                            'created_by' => $user->id,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            
            $message = 'KPI Entry created successfully.';
            if ($hasCapaData) {
                $message .= ' CAPA has been created and linked to this entry.';
            }

            return redirect()->route('kpi.entries.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'error' => 'Failed to create KPI entry: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified KPI entry.
     */
    public function show(KpiEntry $entry)
    {
        Gate::authorize('view kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        $entry->load([
            'template.fields', 
            'department', 
            'creator',
            'capaAreas.problems.causes.actionPlans'
        ]);

        return view('kpi.entries.show', compact('entry'));
    }

    /**
     * Show the form for editing the specified KPI entry.
     */
    public function edit(KpiEntry $entry)
    {
        Gate::authorize('edit kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        $entry->load([
            'template.fields' => function ($query) {
                $query->orderBy('sort_order');
            },
            'department',
            'capaAreas.problems.causes.actionPlans'
        ]);

        // Get existing CAPA area names for dropdown
        $capaAreas = CapaArea::select('area_name')
            ->distinct()
            ->orderBy('area_name')
            ->pluck('area_name');

        return view('kpi.entries.edit', compact('entry', 'capaAreas'));
    }

    /**
     * Update the specified KPI entry.
     */
    public function update(Request $request, KpiEntry $entry)
    {
        Gate::authorize('edit kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        // Auto-calculate status first
        $status = 'OK';
        if ($request->actual < $request->target) {
            $status = 'NG';
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'target' => 'required|numeric',
            'actual' => 'required|numeric',
            'notes' => 'nullable|string',
            'dynamic_fields' => 'nullable|array',
            // CAPA areas (now an array of areas, each with their own problems)
            'capa_areas' => 'nullable|array',
            'capa_areas.*.capa_date' => 'nullable|date',
            'capa_areas.*.area_name' => 'nullable|string',
            'capa_areas.*.area_description' => 'nullable|string',
            'capa_areas.*.problems' => 'nullable|array',
            'capa_areas.*.problems.*.problem_description' => 'nullable|string',
            'capa_areas.*.problems.*.severity' => 'nullable|in:low,medium,high,critical',
            'capa_areas.*.problems.*.causes' => 'nullable|array',
            'capa_areas.*.problems.*.causes.*.cause_description' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans' => 'nullable|array',
            'capa_areas.*.problems.*.causes.*.action_plans.*.description' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.person_in_charge' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.due_date' => 'nullable|date',
            'capa_areas.*.problems.*.causes.*.action_plans.*.keterangan' => 'nullable|string',
            'capa_areas.*.problems.*.causes.*.action_plans.*.status' => 'nullable|in:open,progress,close',
        ]);

        // Check if CAPA data exists
        $hasCapaData = false;
        if (!empty($validated['capa_areas'])) {
            foreach ($validated['capa_areas'] as $areaData) {
                if (!empty($areaData['problems']) && count(array_filter($areaData['problems'], function($problem) {
                    return !empty($problem['problem_description']);
                })) > 0) {
                    $hasCapaData = true;
                    break;
                }
            }
        }

        // Check if CAPA is required but not filled
        if ($status === 'NG' && !$hasCapaData) {
            return back()->withInput()->withErrors([
                'capa' => 'CAPA is required when KPI status is NG. Please add at least one problem.'
            ]);
        }

        DB::transaction(function () use ($validated, $status, $entry, $hasCapaData, $user) {
            // Update KPI Entry
            $entry->update([
                'entry_date' => $validated['entry_date'],
                'target' => $validated['target'],
                'actual' => $validated['actual'],
                'status' => $status,
                'notes' => $validated['notes'],
                'dynamic_fields' => $validated['dynamic_fields'] ?? null,
            ]);

            // Delete existing CAPA data if any
            $entry->capaAreas()->each(function ($capaArea) {
                // Delete cascade will handle problems, causes, and action plans
                $capaArea->delete();
            });

            // Create new CAPA data if provided
            if ($hasCapaData && !empty($validated['capa_areas'])) {
                // Loop through each CAPA area
                foreach ($validated['capa_areas'] as $areaIndex => $areaData) {
                    // Skip if no area name or no problems
                    if (empty($areaData['area_name']) || empty($areaData['problems'])) {
                        continue;
                    }

                    // Check if this area has any valid problems
                    $validProblems = array_filter($areaData['problems'], function($problem) {
                        return !empty($problem['problem_description']);
                    });
                    
                    if (empty($validProblems)) {
                        continue; // Skip areas with no valid problems
                    }

                    // Create CAPA Area
                    $capaArea = CapaArea::create([
                        'department_id' => $entry->department_id,
                        'kpi_entry_id' => $entry->id,
                        'capa_date' => $areaData['capa_date'] ?? $validated['entry_date'],
                        'area_name' => $areaData['area_name'],
                        'area_description' => $areaData['area_description'] ?? null,
                        'is_mandatory' => $status === 'NG',
                        'created_by' => $user->id,
                    ]);

                    // Loop through problems in this area
                    foreach ($areaData['problems'] as $problemIndex => $problemData) {
                        if (empty($problemData['problem_description'])) {
                            continue; // Skip empty problems
                        }

                        // Create CAPA Problem
                        $capaProblem = CapaProblem::create([
                            'capa_area_id' => $capaArea->id,
                            'problem_description' => $problemData['problem_description'],
                            'severity' => $problemData['severity'] ?? 'medium',
                            'created_by' => $user->id,
                        ]);

                        // Loop through causes for this problem
                        if (!empty($problemData['causes'])) {
                            foreach ($problemData['causes'] as $causeData) {
                                if (empty($causeData['cause_description'])) {
                                    continue; // Skip empty causes
                                }

                                // Create CAPA Cause
                                $capaCause = CapaCause::create([
                                    'capa_problem_id' => $capaProblem->id,
                                    'cause_description' => $causeData['cause_description'],
                                    'created_by' => $user->id,
                                ]);

                                // Loop through action plans for this cause
                                if (!empty($causeData['action_plans'])) {
                                    foreach ($causeData['action_plans'] as $actionData) {
                                        if (empty($actionData['description'])) {
                                            continue; // Skip empty actions
                                        }

                                        // Create CAPA Action Plan
                                        CapaActionPlan::create([
                                            'capa_cause_id' => $capaCause->id,
                                            'description' => $actionData['description'],
                                            'person_in_charge' => $actionData['person_in_charge'] ?? $user->name,
                                            'due_date' => $actionData['due_date'] ?? now()->addDays(30),
                                            'keterangan' => $actionData['keterangan'] ?? null,
                                            'status' => $actionData['status'] ?? 'open',
                                            'created_by' => $user->id,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });

        $message = 'KPI Entry updated successfully.';
        if ($hasCapaData) {
            $message .= ' CAPA has been updated.';
        }

        return redirect()->route('kpi.entries.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified KPI entry.
     */
    public function destroy(KpiEntry $entry)
    {
        Gate::authorize('delete kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        $entry->delete();

        return redirect()->route('kpi.entries.index')
            ->with('success', 'KPI Entry deleted successfully.');
    }

}
