<?php

namespace App\Http\Controllers;

use App\Models\KpiEntry;
use App\Models\KpiMonthlyTarget;
use App\Models\KpiDefinition;
use App\Models\KpiTemplate;
use App\Models\Department;
use App\Models\CapaArea;
use App\Models\CapaProblem;
use App\Models\CapaCause;
use App\Models\CapaActionPlan;
use Carbon\Carbon;
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
        $query = KpiEntry::with(['template.departments', 'department'])
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
            ->with(['departments' => function ($q) use ($user, $request) {
                $departmentId = $request->filled('department') ? (int) $request->department : null;
                if ($departmentId) {
                    $q->where('departments.id', $departmentId);
                } elseif (!$user->can_access_all_departments) {
                    $q->where('departments.id', $user->department_id);
                }
            }])
            ->when($request->filled('department'), function ($q) use ($request) {
                $departmentId = (int) $request->department;
                return $q->whereHas('departments', function ($deptQuery) use ($departmentId) {
                    $deptQuery->where('departments.id', $departmentId)
                        ->where('kpi_template_departments.is_active', 1);
                });
            })
            ->when(!$user->can_access_all_departments && !$request->filled('department'), function ($q) use ($user) {
                return $q->whereHas('departments', function ($deptQuery) use ($user) {
                    $deptQuery->where('departments.id', $user->department_id)
                        ->where('kpi_template_departments.is_active', 1);
                });
            })
            ->orderBy('code')->get();

        return view('kpi.entries.index', compact('entries', 'departments', 'templates'));
    }

    /**
     * Show the form for creating a new KPI entry.
     */
    public function create(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();
        $selectedDepartmentId = $request->filled('department_id') ? (int) $request->department_id : null;
        if (!$user->can_access_all_departments) {
            $selectedDepartmentId = $user->department_id;
        }

        if ($user->can_access_all_departments && !$selectedDepartmentId) {
            $kpis = collect();
        } else {
            $kpis = KpiDefinition::query()
                ->where('department_id', (int) $selectedDepartmentId)
                ->where('is_active', 1)
                ->with([
                    'template' => function ($q) {
                        $q->where('is_active', 1);
                    },
                    'template.fields' => function ($q) {
                        $q->orderBy('sort_order');
                    },
                ])
                ->orderByRaw('COALESCE(sort_order, 999999) asc')
                ->orderBy('id')
                ->get()
                ->filter(fn ($kpi) => (bool) $kpi->template);
        }

        $selectedKpi = null;
        if ($request->filled('kpi_definition_id')) {
            $selectedKpi = $kpis->firstWhere('id', (int) $request->kpi_definition_id);
        }

        // Get existing CAPA area names for dropdown
        $capaAreas = CapaArea::select('area_name')
            ->distinct()
            ->orderBy('area_name')
            ->pluck('area_name');

        return view('kpi.entries.create', compact('kpis', 'selectedKpi', 'capaAreas', 'selectedDepartmentId'));
    }

    /**
     * Store a newly created KPI entry.
     */
    public function store(Request $request)
    {
        Gate::authorize('create kpi');

        // Batch mode: entries[kpi_definition_id][...]
        if ($request->has('entries') && is_array($request->input('entries'))) {
            return $this->storeBatch($request);
        }

        $user = auth()->user();

        $templateForRules = null;
        if ($request->filled('kpi_definition_id')) {
            $defForRules = KpiDefinition::with('template')->find($request->kpi_definition_id);
            $templateForRules = $defForRules?->template;
        }
        $actualRule = ($templateForRules && $templateForRules->actual_mode === 'aggregated')
            ? 'nullable|numeric'
            : 'required|numeric';

        $validated = $request->validate([
            'kpi_definition_id' => 'required|exists:kpi_template_departments,id',
            'department_id' => $user->can_access_all_departments ? 'required|exists:departments,id' : 'nullable',
            'entry_date' => 'required|date',
            'target' => 'required|numeric',
            'actual' => $actualRule,
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

        $user = auth()->user();

        $departmentId = $user->can_access_all_departments
            ? (int) $validated['department_id']
            : $user->department_id;

        if (!$user->canAccessDepartment($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        $kpiDefinition = KpiDefinition::query()
            ->where('id', (int) $validated['kpi_definition_id'])
            ->with(['template'])
            ->firstOrFail();

        if ((int) $kpiDefinition->department_id !== (int) $departmentId) {
            return back()->withInput()->withErrors([
                'kpi_definition_id' => 'Selected KPI does not belong to the selected department.'
            ]);
        }

        if (!$kpiDefinition->is_active || !$kpiDefinition->template || !$kpiDefinition->template->is_active) {
            return back()->withInput()->withErrors([
                'kpi_definition_id' => 'Selected KPI is inactive or missing its template.'
            ]);
        }

        $template = $kpiDefinition->template;

        $dynamicFields = $validated['dynamic_fields'] ?? [];
        if ($template->actual_mode === 'aggregated') {
            $computedActual = $this->computeAggregatedActual($template, $dynamicFields);
            if ($computedActual === null) {
                return back()->withInput()->withErrors([
                    'actual' => 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.'
                ]);
            }

            $validated['actual'] = $computedActual;
        }

        // Auto-calculate status
        $targetYear = Carbon::parse($validated['entry_date'])->year;
        $targetOperator = KpiMonthlyTarget::query()
            ->where('kpi_definition_id', (int) $validated['kpi_definition_id'])
            ->where('target_year', $targetYear)
            ->where('target_month', 1)
            ->value('target_operator') ?: 'gte';

        $status = $this->computeKpiStatus(
            (float) $validated['actual'],
            (float) $validated['target'],
            $targetOperator
        );

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
                'kpi_definition_id' => (int) $validated['kpi_definition_id'],
                'kpi_template_id' => (int) $template->id,
                'department_id' => $departmentId,
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
                        'department_id' => $departmentId,
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

    private function storeBatch(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();

        $validated = $request->validate([
            'department_id' => $user->can_access_all_departments ? 'required|exists:departments,id' : 'nullable',
            'entry_date' => 'required|date',
            'entries' => 'required|array',
            'entries.*.target' => 'nullable|numeric',
            'entries.*.actual' => 'nullable|numeric',
            'entries.*.notes' => 'nullable|string',
            'entries.*.dynamic_fields' => 'nullable|array',
            'entries.*.capa_areas' => 'nullable|array',
        ]);

        $departmentId = $user->can_access_all_departments
            ? (int) $validated['department_id']
            : $user->department_id;

        if (!$user->canAccessDepartment($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        $entryDate = $validated['entry_date'];
        $targetYear = Carbon::parse($entryDate)->year;

        $entriesPayload = $validated['entries'] ?? [];
        $requestedKpiIds = array_values(array_filter(array_map('intval', array_keys($entriesPayload))));
        if (empty($requestedKpiIds)) {
            return back()->withInput()->withErrors([
                'entries' => 'No KPIs were submitted.'
            ]);
        }

        $kpiDefinitions = KpiDefinition::query()
            ->where('department_id', $departmentId)
            ->whereIn('id', $requestedKpiIds)
            ->with([
                'template' => function ($q) {
                    $q->where('is_active', 1);
                },
                'template.fields' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->get()
            ->filter(fn ($kpi) => (bool) $kpi->template)
            ->keyBy('id');

        $errors = [];
        $entriesToCreate = [];

        foreach ($requestedKpiIds as $kpiId) {
            $payload = is_array($entriesPayload[$kpiId] ?? null) ? $entriesPayload[$kpiId] : [];
            $dynamicFields = is_array($payload['dynamic_fields'] ?? null) ? $payload['dynamic_fields'] : [];
            $capaAreas = is_array($payload['capa_areas'] ?? null) ? $payload['capa_areas'] : [];
            $notes = isset($payload['notes']) ? (string) $payload['notes'] : '';

            $hasAnyDynamic = count(array_filter($dynamicFields, fn ($v) => $v !== null && $v !== '')) > 0;
            $hasAnyCapa = $this->hasCapaData($capaAreas);
            $hasAnyInput = (
                ($payload['actual'] ?? null) !== null && ($payload['actual'] ?? '') !== ''
            ) || (
                ($payload['target'] ?? null) !== null && ($payload['target'] ?? '') !== ''
            ) || $hasAnyDynamic || $hasAnyCapa || trim($notes) !== '';

            if (!$hasAnyInput) {
                continue;
            }

            $kpiDefinition = $kpiDefinitions->get($kpiId);
            if (!$kpiDefinition) {
                $errors["entries.$kpiId.kpi_definition_id"] = 'Invalid KPI for selected department.';
                continue;
            }

            if (!$kpiDefinition->is_active) {
                $errors["entries.$kpiId.kpi_definition_id"] = 'KPI is inactive.';
                continue;
            }

            $template = $kpiDefinition->template;
            if (!$template || !$template->is_active) {
                $errors["entries.$kpiId.kpi_definition_id"] = 'KPI is missing an active template.';
                continue;
            }

            // Resolve target: use submitted value, otherwise fall back to yearly target.
            $targetValue = $payload['target'] ?? null;
            if ($targetValue === '' || $targetValue === null) {
                $targetValue = KpiMonthlyTarget::query()
                    ->where('kpi_definition_id', $kpiId)
                    ->where('target_year', $targetYear)
                    ->where('target_month', 1)
                    ->value('target');
            }
            if ($targetValue === null || $targetValue === '') {
                $errors["entries.$kpiId.target"] = 'Target is required (yearly target not set).';
                continue;
            }

            // Resolve actual: manual or aggregated.
            $actualValue = $payload['actual'] ?? null;
            if ($template->actual_mode === 'aggregated') {
                $computedActual = $this->computeAggregatedActual($template, $dynamicFields);
                if ($computedActual === null) {
                    $errors["entries.$kpiId.actual"] = 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.';
                    continue;
                }
                $actualValue = $computedActual;
            } else {
                if ($actualValue === null || $actualValue === '') {
                    $errors["entries.$kpiId.actual"] = 'Actual is required.';
                    continue;
                }
            }

            $targetOperator = KpiMonthlyTarget::query()
                ->where('kpi_definition_id', $kpiId)
                ->where('target_year', $targetYear)
                ->where('target_month', 1)
                ->value('target_operator') ?: 'gte';

            $status = $this->computeKpiStatus(
                (float) $actualValue,
                (float) $targetValue,
                $targetOperator
            );

            if ($status === 'NG' && !$hasAnyCapa) {
                $errors["entries.$kpiId.capa"] = 'CAPA is required when KPI status is NG. Please add at least one problem.';
                continue;
            }

            $entriesToCreate[$kpiId] = [
                'kpi_definition_id' => $kpiId,
                'kpi_template_id' => (int) $template->id,
                'department_id' => $departmentId,
                'entry_date' => $entryDate,
                'target' => (float) $targetValue,
                'actual' => (float) $actualValue,
                'status' => $status,
                'notes' => $notes ?: null,
                'dynamic_fields' => $dynamicFields ?: null,
                'created_by' => $user->id,
                'capa_areas' => $capaAreas,
            ];
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors($errors);
        }

        if (empty($entriesToCreate)) {
            return back()->withInput()->withErrors([
                'entries' => 'Nothing to submit. Fill at least one KPI before submitting.'
            ]);
        }

        // Prevent duplicates per (kpi_definition_id, entry_date)
        $duplicateKpiIds = KpiEntry::query()
            ->whereDate('entry_date', $entryDate)
            ->whereIn('kpi_definition_id', array_keys($entriesToCreate))
            ->pluck('kpi_definition_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($duplicateKpiIds)) {
            $dupErrors = [];
            foreach ($duplicateKpiIds as $dupId) {
                $dupErrors["entries.$dupId.actual"] = 'An entry already exists for this KPI and date.';
            }
            return back()->withInput()->withErrors($dupErrors);
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;
            $createdCapaCount = 0;

            foreach ($entriesToCreate as $kpiId => $entryData) {
                $capaAreas = $entryData['capa_areas'] ?? [];
                unset($entryData['capa_areas']);

                $entry = KpiEntry::create($entryData);
                $createdCount++;

                if ($this->hasCapaData($capaAreas)) {
                    foreach ($capaAreas as $areaData) {
                        if (!is_array($areaData)) {
                            continue;
                        }

                        if (empty($areaData['area_name']) || empty($areaData['problems']) || !is_array($areaData['problems'])) {
                            continue;
                        }

                        $validProblems = array_filter($areaData['problems'], function ($problem) {
                            return is_array($problem) && !empty($problem['problem_description']);
                        });

                        if (empty($validProblems)) {
                            continue;
                        }

                        $capaArea = CapaArea::create([
                            'department_id' => $departmentId,
                            'kpi_entry_id' => $entry->id,
                            'capa_date' => $areaData['capa_date'] ?? $entryDate,
                            'area_name' => $areaData['area_name'],
                            'area_description' => $areaData['area_description'] ?? null,
                            'is_mandatory' => $entry->status === 'NG',
                            'created_by' => $user->id,
                        ]);

                        foreach ($areaData['problems'] as $problemData) {
                            if (!is_array($problemData) || empty($problemData['problem_description'])) {
                                continue;
                            }

                            $capaProblem = CapaProblem::create([
                                'capa_area_id' => $capaArea->id,
                                'problem_description' => $problemData['problem_description'],
                                'severity' => $problemData['severity'] ?? 'medium',
                                'created_by' => $user->id,
                            ]);

                            if (!empty($problemData['causes']) && is_array($problemData['causes'])) {
                                foreach ($problemData['causes'] as $causeData) {
                                    if (!is_array($causeData) || empty($causeData['cause_description'])) {
                                        continue;
                                    }

                                    $capaCause = CapaCause::create([
                                        'capa_problem_id' => $capaProblem->id,
                                        'cause_description' => $causeData['cause_description'],
                                        'created_by' => $user->id,
                                    ]);

                                    if (!empty($causeData['action_plans']) && is_array($causeData['action_plans'])) {
                                        foreach ($causeData['action_plans'] as $actionData) {
                                            if (!is_array($actionData) || empty($actionData['description'])) {
                                                continue;
                                            }

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

                        $createdCapaCount++;
                    }
                }
            }

            DB::commit();

            $message = "Created {$createdCount} KPI entries.";
            if ($createdCapaCount > 0) {
                $message .= " CAPA created for {$createdCapaCount} KPI(s).";
            }

            return redirect()->route('kpi.entries.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'error' => 'Failed to create KPI entries: ' . $e->getMessage()
            ]);
        }
    }

    private function hasCapaData(array $capaAreas): bool
    {
        foreach ($capaAreas as $areaData) {
            if (!is_array($areaData)) {
                continue;
            }
            if (empty($areaData['problems']) || !is_array($areaData['problems'])) {
                continue;
            }
            foreach ($areaData['problems'] as $problemData) {
                if (is_array($problemData) && !empty($problemData['problem_description'])) {
                    return true;
                }
            }
        }
        return false;
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
            'template.departments',
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
            'template.departments',
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

        $actualRule = $entry->template && $entry->template->actual_mode === 'aggregated'
            ? 'nullable|numeric'
            : 'required|numeric';

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'target' => 'required|numeric',
            'actual' => $actualRule,
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

        $dynamicFields = $validated['dynamic_fields'] ?? [];
        if ($entry->template && $entry->template->actual_mode === 'aggregated') {
            $computedActual = $this->computeAggregatedActual($entry->template, $dynamicFields);
            if ($computedActual === null) {
                return back()->withInput()->withErrors([
                    'actual' => 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.'
                ]);
            }

            $validated['actual'] = $computedActual;
        }

        // Auto-calculate status
        $targetYear = Carbon::parse($validated['entry_date'])->year;
        $targetOperator = KpiMonthlyTarget::query()
            ->where('kpi_template_id', (int) $entry->kpi_template_id)
            ->where('department_id', (int) $entry->department_id)
            ->where('target_year', $targetYear)
            ->where('target_month', 1)
            ->value('target_operator') ?: 'gte';

        $status = $this->computeKpiStatus(
            (float) $validated['actual'],
            (float) $validated['target'],
            $targetOperator
        );

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

    private function computeKpiStatus(float $actual, float $target, ?string $operator): string
    {
        $operator = $operator ?: 'gte';

        if ($operator === 'lte') {
            return $actual <= $target ? 'OK' : 'NG';
        }

        // Default: higher is better
        return $actual >= $target ? 'OK' : 'NG';
    }

    private function computeAggregatedActual(KpiTemplate $template, array $dynamicFields): ?float
    {
        $fieldKeys = $template->actual_field_keys ?? [];
        if (empty($fieldKeys)) {
            return null;
        }

        $values = [];
        foreach ($fieldKeys as $key) {
            if (array_key_exists($key, $dynamicFields) && is_numeric($dynamicFields[$key])) {
                $values[] = (float) $dynamicFields[$key];
            }
        }

        if (empty($values)) {
            return null;
        }

        switch ($template->actual_aggregation) {
            case 'avg':
                return array_sum($values) / count($values);
            case 'min':
                return min($values);
            case 'max':
                return max($values);
            case 'sum':
            default:
                return array_sum($values);
        }
    }

}
