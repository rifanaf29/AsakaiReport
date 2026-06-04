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
use App\Services\KpiRejectionWasteNgSync;
use Illuminate\Support\Facades\Http;

class KpiEntryController extends Controller
{
    private const TEMPLATE_CNC_WASTE = 'TPL_PD_WASTE_CNC_BENDING';
    private const TEMPLATE_PD_MP_OT = 'TPL_PD_MP_OT';
    /**
     * Display a listing of KPI entries.
     */
    public function index(Request $request)
    {
        Gate::authorize('view kpi');

        $user = auth()->user();
        $query = KpiEntry::with(['template', 'kpiDefinition', 'department'])
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

        // KPI definition filter
        if ($request->filled('kpi_definition')) {
            $query->where('kpi_definition_id', $request->kpi_definition);
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

        $kpiDefinitions = KpiDefinition::query()
            ->where('is_active', 1)
            ->with('template')
            ->when($request->filled('department'), function ($q) use ($request) {
                return $q->where('department_id', (int) $request->department);
            })
            ->when(!$user->can_access_all_departments && !$request->filled('department'), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderByRaw('COALESCE(sort_order, 999999) asc')
            ->orderBy('id')
            ->get();

        return view('kpi.entries.index', compact('entries', 'departments', 'kpiDefinitions'));
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

        $selectedDepartmentCode = null;
        if ($selectedDepartmentId) {
            $selectedDepartmentCode = Department::query()->whereKey((int) $selectedDepartmentId)->value('code');
        }

        return view('kpi.entries.create', compact('kpis', 'selectedKpi', 'capaAreas', 'selectedDepartmentId', 'selectedDepartmentCode'));
    }

    /**
     * Prefill Maintenance (MN) KPI actuals by calling the Maintenance KPI API.
     *
     * API response should include:
    * - total_downtime_hour (float) OR downtime (float)
    * - total_tickets (int) OR finish (int)
     *
     * Input:
     * - date (YYYY-MM-DD)
     * - working_hours (float)
     * - department_id (required for users with cross-dept access)
     * - kpi_definition_ids[]
     */
    public function mnPrefill(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();

        $dateStr = (string) $request->query('date', '');
        if (trim($dateStr) === '') {
            return response()->json(['data' => []]);
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid date'], 422);
        }

        $departmentId = null;
        if ($user->can_access_all_departments) {
            $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        } else {
            $departmentId = (int) $user->department_id;
        }

        if (!$departmentId) {
            return response()->json(['data' => []]);
        }

        if (!$user->canAccessDepartment($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        $deptCode = Department::query()->whereKey($departmentId)->value('code');
        if ($deptCode !== 'MN') {
            return response()->json(['data' => []]);
        }

        $workingHoursRaw = $request->query('working_hours');
        if (!is_numeric($workingHoursRaw) || (float) $workingHoursRaw <= 0) {
            return response()->json(['message' => 'working_hours is required'], 422);
        }
        $workingHours = (float) $workingHoursRaw;

        $defIdsRaw = $request->query('kpi_definition_ids', []);
        if (!is_array($defIdsRaw)) {
            $defIdsRaw = [];
        }
        $kpiDefinitionIds = array_values(array_unique(array_filter(array_map('intval', $defIdsRaw), fn ($id) => $id > 0)));
        if (!$kpiDefinitionIds) {
            return response()->json(['data' => []]);
        }

        $definitions = KpiDefinition::query()
            ->whereIn('id', $kpiDefinitionIds)
            ->where('department_id', $departmentId)
            ->where('is_active', 1)
            ->get(['id', 'display_name']);

        if ($definitions->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $apiUrl = rtrim((string) env('MN_KPI_API_URL', 'http://localhost:3000/api/mn-kpi'));
        if ($apiUrl === '') {
            return response()->json(['data' => []]);
        }

        // Respect MN_KPI_API_URL exactly as configured.
        // If it already includes a query string (e.g., ?period=yesterday), do not append date.
        $hasQueryInEnvUrl = false;
        try {
            $parts = parse_url($apiUrl);
            $hasQueryInEnvUrl = is_array($parts) && !empty($parts['query']);
        } catch (\Throwable $e) {
            $hasQueryInEnvUrl = false;
        }

        $client = Http::acceptJson()->timeout(8);

        try {
            // Prefer date-aware request when supported (only if env URL does not fix a period/query).
            $res = $hasQueryInEnvUrl
                ? $client->get($apiUrl)
                : $client->get($apiUrl, ['date' => $date->toDateString()]);
        } catch (\Throwable $e) {
            $res = null;
        }

        if (!$res || !$res->ok()) {
            // Fallback: API that always returns "current day" and does not accept query params.
            try {
                $res = $client->get($apiUrl);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Failed to call MN KPI API'], 502);
            }
        }

        if (!$res->ok()) {
            return response()->json(['message' => 'MN KPI API returned error'], 502);
        }

        $json = $res->json();
        $payload = is_array($json) ? ($json['data'] ?? $json) : [];
        if (!is_array($payload)) {
            $payload = [];
        }

        $downtimeHour = null;
        $downtimeKey = null;
        foreach (['total_downtime_hour', 'total_downtime_hours', 'total_downtime', 'downtime_hour', 'downtime_hours', 'downtime'] as $k) {
            if (array_key_exists($k, $payload) && is_numeric($payload[$k])) {
                $downtimeHour = (float) $payload[$k];
                $downtimeKey = $k;
                break;
            }
        }

        $totalTickets = null;
        $ticketsKey = null;
        foreach (['total_tickets', 'tickets', 'ticket_total', 'finish'] as $k) {
            if (array_key_exists($k, $payload) && is_numeric($payload[$k])) {
                $totalTickets = (int) $payload[$k];
                $ticketsKey = $k;
                break;
            }
        }

        $computed = [
            'downtime_pct' => null,
            'mttr' => null,
            'mtbf' => null,
        ];

        if ($downtimeHour !== null) {
            $computed['downtime_pct'] = $workingHours > 0 ? ($downtimeHour / $workingHours) * 100.0 : null;
        }
        if ($downtimeHour !== null && $totalTickets !== null && $totalTickets > 0) {
            $computed['mttr'] = $downtimeHour / $totalTickets;
            $computed['mtbf'] = $workingHours / $totalTickets;
        }

        $data = [];
        foreach ($definitions as $def) {
            $name = strtolower((string) $def->display_name);

            if (str_contains($name, 'downtime') && $computed['downtime_pct'] !== null) {
                $data[(int) $def->id] = ['actual' => $computed['downtime_pct']];
                continue;
            }
            if (str_contains($name, 'mttr') && $computed['mttr'] !== null) {
                $data[(int) $def->id] = ['actual' => $computed['mttr']];
                continue;
            }
            if (str_contains($name, 'mtbf') && $computed['mtbf'] !== null) {
                $data[(int) $def->id] = ['actual' => $computed['mtbf']];
                continue;
            }
        }

        return response()->json([
            'data' => $data,
            'source' => [
                'date' => $date->toDateString(),
                'total_downtime_hour' => $downtimeHour,
                'total_tickets' => $totalTickets,
                'working_hours' => $workingHours,
                'payload_keys_used' => [
                    'downtime' => $downtimeKey,
                    'tickets' => $ticketsKey,
                ],
            ],
            'computed' => $computed,
        ]);
    }

    /**
     * Month-to-date accumulation (akumulasi) for additional fields.
     *
     * Returns sums of numeric additional fields across all dates in the selected month,
     * grouped by kpi_definition_id.
     */
    public function akumulasi(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();

        $dateStr = (string) $request->query('date', '');
        if (trim($dateStr) === '') {
            return response()->json(['data' => []]);
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid date'], 422);
        }

        $departmentId = null;
        if ($user->can_access_all_departments) {
            $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        } else {
            $departmentId = (int) $user->department_id;
        }

        if (!$departmentId) {
            return response()->json(['data' => []]);
        }

        if (!$user->canAccessDepartment($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        $idsRaw = $request->query('kpi_definition_ids', []);
        if (is_string($idsRaw)) {
            $kpiDefinitionIds = array_filter(array_map('intval', preg_split('/\s*,\s*/', $idsRaw)));
        } elseif (is_array($idsRaw)) {
            $kpiDefinitionIds = array_filter(array_map('intval', $idsRaw));
        } else {
            $kpiDefinitionIds = [];
        }

        $kpiDefinitionIds = array_values(array_unique(array_filter($kpiDefinitionIds, fn ($id) => $id > 0)));
        if (!$kpiDefinitionIds) {
            return response()->json(['data' => []]);
        }

        $definitions = KpiDefinition::query()
            ->whereIn('id', $kpiDefinitionIds)
            ->where('department_id', $departmentId)
            ->where('is_active', 1)
            ->with([
                'template' => function ($q) {
                    $q->where('is_active', 1);
                },
                'template.fields' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->get()
            ->filter(fn ($kpi) => (bool) $kpi->template);

        $defIds = $definitions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        if (!$defIds) {
            return response()->json(['data' => []]);
        }

        $numericKeysByDef = [];
        foreach ($definitions as $def) {
            $keys = $def->template->fields
                ->where('field_type', '!=', 'calculated')
                ->filter(fn ($f) => in_array($f->field_type, ['number', 'decimal', 'accounting'], true))
                ->pluck('field_key')
                ->filter()
                ->values()
                ->all();
            $numericKeysByDef[(int) $def->id] = $keys;
        }

        $start = $date->copy()->startOfMonth()->toDateString();
        $end = $date->copy()->endOfMonth()->toDateString();

        $entries = KpiEntry::query()
            ->whereIn('kpi_definition_id', $defIds)
            ->whereBetween('entry_date', [$start, $end])
            ->get(['kpi_definition_id', 'dynamic_fields']);

        $sums = [];
        foreach ($defIds as $defId) {
            $defId = (int) $defId;
            $sums[$defId] = [];
            foreach (($numericKeysByDef[$defId] ?? []) as $key) {
                $sums[$defId][$key] = 0.0;
            }
        }

        foreach ($entries as $entry) {
            $defId = (int) $entry->kpi_definition_id;
            $fields = is_array($entry->dynamic_fields) ? $entry->dynamic_fields : [];

            foreach (($numericKeysByDef[$defId] ?? []) as $key) {
                $val = $fields[$key] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                if (!is_numeric($val)) {
                    continue;
                }
                $sums[$defId][$key] = ($sums[$defId][$key] ?? 0.0) + (float) $val;
            }
        }

        return response()->json([
            'data' => $sums,
            'month' => $date->format('Y-m'),
        ]);
    }

    /**
     * Existing entries for the selected date.
     *
     * Used by the batch create form to show that (kpi_definition_id, entry_date) is unique
     * and prevent users from thinking they must fill already-submitted KPIs.
     */
    public function existing(Request $request)
    {
        Gate::authorize('create kpi');

        $user = auth()->user();

        $dateStr = (string) $request->query('date', '');
        if (trim($dateStr) === '') {
            return response()->json(['data' => []]);
        }

        try {
            $date = Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid date'], 422);
        }

        $departmentId = null;
        if ($user->can_access_all_departments) {
            $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        } else {
            $departmentId = (int) $user->department_id;
        }

        if (!$departmentId) {
            return response()->json(['data' => []]);
        }

        if (!$user->canAccessDepartment($departmentId)) {
            abort(403);
        }

        $defIds = $request->query('kpi_definition_ids', []);
        if (!is_array($defIds)) {
            $defIds = [];
        }
        $defIds = array_values(array_filter(array_map('intval', $defIds)));
        if (empty($defIds)) {
            return response()->json(['data' => []]);
        }

        $validDefIds = KpiDefinition::query()
            ->where('department_id', $departmentId)
            ->whereIn('id', $defIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($validDefIds)) {
            return response()->json(['data' => []]);
        }

        $entries = KpiEntry::query()
            ->where('department_id', $departmentId)
            ->whereDate('entry_date', $date)
            ->whereIn('kpi_definition_id', $validDefIds)
            ->get(['id', 'kpi_definition_id', 'target', 'actual', 'status', 'notes']);

        $data = [];
        foreach ($entries as $entry) {
            $entryId = (int) $entry->id;
            $defId = (int) $entry->kpi_definition_id;
            $data[$defId] = [
                'id' => $entryId,
                'target' => $entry->target,
                'actual' => $entry->actual,
                'status' => $entry->status,
                'notes' => $entry->notes,
                'edit_url' => route('kpi.entries.edit', $entryId),
                'show_url' => route('kpi.entries.show', $entryId),
            ];
        }

        return response()->json([
            'data' => $data,
            'date' => $date,
        ]);
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
        $actualRule = ($templateForRules && (
                $templateForRules->actual_mode === 'aggregated' ||
                $templateForRules->target_mode === 'display_only'
            ))
            ? 'nullable|numeric'
            : 'required|numeric';
        $targetRule = ($templateForRules && $templateForRules->target_mode === 'display_only')
            ? 'nullable|numeric'
            : 'required|numeric';

        $validated = $request->validate([
            'kpi_definition_id' => 'required|exists:kpi_template_departments,id',
            'department_id' => $user->can_access_all_departments ? 'required|exists:departments,id' : 'nullable',
            'entry_date' => 'required|date',
            'target' => $targetRule,
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
        $dynamicFields = $this->enrichDynamicFieldsForTemplate($template, $dynamicFields);
        $validated['dynamic_fields'] = $dynamicFields;

        if ($error = $this->validateRejectionHasilProduksi($template, $dynamicFields, 'dynamic_fields.hasil_produksi')) {
            return back()->withInput()->withErrors(['dynamic_fields.hasil_produksi' => $error]);
        }

        if ($template->actual_mode === 'aggregated') {
            $computedActual = $this->computeAggregatedActual($template, $dynamicFields);
            if ($computedActual === null) {
                $message = ($template->actual_aggregation === 'formula')
                    ? 'Actual is computed by formula. Please enter numeric values for all referenced fields.'
                    : 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.';
                return back()->withInput()->withErrors([
                    'actual' => $message,
                ]);
            }

            $validated['actual'] = $computedActual;
        }

        // Auto-calculate status
        $targetYear = Carbon::parse($validated['entry_date'])->year;
        if ($template->target_mode === 'display_only') {
            $status = null;
            $validated['target'] = null;
        } else {
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

            $this->syncRejectionHasilProduksiIfNeeded($template, $validated['entry_date'], $validated['dynamic_fields'] ?? []);

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

        $singleKpiId = $request->input('submit_kpi_definition_id');
        $singleKpiId = ($singleKpiId === null || $singleKpiId === '') ? null : (int) $singleKpiId;
        if ($singleKpiId) {
            // Only save the selected KPI, even if other KPIs have input.
            $requestedKpiIds = [$singleKpiId];
            $singlePayload = $entriesPayload[$singleKpiId] ?? $entriesPayload[(string) $singleKpiId] ?? null;
            if (!is_array($singlePayload)) {
                return back()->withInput()->withErrors([
                    'entries' => 'Selected KPI payload is missing.'
                ]);
            }
            $entriesPayload = [$singleKpiId => $singlePayload];
        }
        if (empty($requestedKpiIds)) {
            return back()->withInput()->withErrors([
                'entries' => 'No KPIs were submitted.'
            ]);
        }

        // Idempotency guard: never create duplicates for the same KPI + date.
        // This protects against re-submitting a KPI after saving it individually.
        $existingEntryIdsByKpiId = KpiEntry::query()
            ->where('department_id', $departmentId)
            ->whereDate('entry_date', $entryDate)
            ->whereIn('kpi_definition_id', $requestedKpiIds)
            ->pluck('id', 'kpi_definition_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($singleKpiId && isset($existingEntryIdsByKpiId[$singleKpiId])) {
            return redirect()
                ->route('kpi.entries.edit', $existingEntryIdsByKpiId[$singleKpiId])
                ->with('success', 'This KPI has already been submitted for the selected date. Opened the existing entry for editing.');
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
        $skippedExistingCount = 0;

        foreach ($requestedKpiIds as $kpiId) {
            if (isset($existingEntryIdsByKpiId[$kpiId])) {
                $skippedExistingCount++;
                continue;
            }

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

            $dynamicFields = $this->enrichDynamicFieldsForTemplate($template, $dynamicFields);

            if ($error = $this->validateRejectionHasilProduksi($template, $dynamicFields, "entries.$kpiId.dynamic_fields.hasil_produksi")) {
                $errors["entries.$kpiId.dynamic_fields.hasil_produksi"] = $error;
                continue;
            }

            // Resolve target: skip for display_only templates.
            $targetValue = null;
            if ($template->target_mode !== 'display_only') {
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
            }

            // Resolve actual: manual or aggregated.
            $actualValue = $payload['actual'] ?? null;
            if ($template->actual_mode === 'aggregated') {
                $computedActual = $this->computeAggregatedActual($template, $dynamicFields);
                if ($computedActual === null) {
                    $message = ($template->actual_aggregation === 'formula')
                        ? 'Actual is computed by formula. Please enter numeric values for all referenced fields.'
                        : 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.';
                    $errors["entries.$kpiId.actual"] = $message;
                    continue;
                }
                $actualValue = $computedActual;
            } elseif ($template->target_mode !== 'display_only') {
                if ($actualValue === null || $actualValue === '') {
                    $errors["entries.$kpiId.actual"] = 'Actual is required.';
                    continue;
                }
            }

            if ($template->target_mode === 'display_only') {
                $status = null;
            } else {
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
            }

            $entriesToCreate[$kpiId] = [
                'kpi_definition_id' => $kpiId,
                'kpi_template_id' => (int) $template->id,
                'department_id' => $departmentId,
                'entry_date' => $entryDate,
                'target' => $targetValue !== null ? (float) $targetValue : null,
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
                'entries' => $skippedExistingCount > 0
                    ? 'All submitted KPIs were already submitted for this date.'
                    : 'Nothing to submit. Fill at least one KPI before submitting.'
            ]);
        }

        // Safety check: prevent duplicates if anything slips through.
        $duplicateKpiIds = KpiEntry::query()
            ->where('department_id', $departmentId)
            ->whereDate('entry_date', $entryDate)
            ->whereIn('kpi_definition_id', array_keys($entriesToCreate))
            ->pluck('kpi_definition_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($duplicateKpiIds)) {
            foreach ($duplicateKpiIds as $dupId) {
                unset($entriesToCreate[$dupId]);
            }
            if (empty($entriesToCreate)) {
                return back()->withInput()->withErrors([
                    'entries' => 'All submitted KPIs were already submitted for this date.'
                ]);
            }
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

                $this->syncRejectionHasilProduksiIfNeeded(
                    $kpiDefinitions->get($kpiId)?->template,
                    $entryDate,
                    is_array($entryData['dynamic_fields'] ?? null) ? $entryData['dynamic_fields'] : []
                );

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

            if ($skippedExistingCount > 0) {
                $message .= " Skipped {$skippedExistingCount} KPI(s) already submitted for this date.";
            }

            if ($singleKpiId) {
                // Stay on create page and preserve other (unsaved) KPI inputs.
                $input = $request->all();
                unset($input['submit_kpi_definition_id']);
                if (isset($input['entries']) && is_array($input['entries'])) {
                    unset($input['entries'][$singleKpiId]);
                    unset($input['entries'][(string) $singleKpiId]);
                }

                return redirect()->back()
                    ->with('success', "Saved 1 KPI entry.")
                    ->withInput($input);
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
            'kpiDefinition',
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
            'kpiDefinition',
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

        $actualRule = $entry->template && (
                $entry->template->actual_mode === 'aggregated' ||
                $entry->template->target_mode === 'display_only'
            )
            ? 'nullable|numeric'
            : 'required|numeric';
        $targetRule = $entry->template && $entry->template->target_mode === 'display_only'
            ? 'nullable|numeric'
            : 'required|numeric';

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'target' => $targetRule,
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
        if ($entry->template) {
            $dynamicFields = $this->enrichDynamicFieldsForTemplate($entry->template, $dynamicFields);
        }
        $validated['dynamic_fields'] = $dynamicFields;

        if ($entry->template && ($error = $this->validateRejectionHasilProduksi($entry->template, $dynamicFields, 'dynamic_fields.hasil_produksi'))) {
            return back()->withInput()->withErrors(['dynamic_fields.hasil_produksi' => $error]);
        }

        if ($entry->template && $entry->template->target_mode === 'display_only') {
            $validated['actual'] = null;
            $validated['target'] = null;
            $status = null;
        } else {
            if ($entry->template && $entry->template->actual_mode === 'aggregated') {
                $computedActual = $this->computeAggregatedActual($entry->template, $dynamicFields);
                if ($computedActual === null) {
                    $message = ($entry->template->actual_aggregation === 'formula')
                        ? 'Actual is computed by formula. Please enter numeric values for all referenced fields.'
                        : 'Actual value is aggregated from selected fields. Please enter numeric values for the configured fields.';
                    return back()->withInput()->withErrors([
                        'actual' => $message,
                    ]);
                }
                $validated['actual'] = $computedActual;
            }

            $targetYear = Carbon::parse($validated['entry_date'])->year;
            $targetOperator = KpiMonthlyTarget::query()
                ->where('kpi_template_id', (int) $entry->kpi_template_id)
                ->where('department_id', (int) $entry->department_id)
                ->where('target_year', $targetYear)
                ->where('target_month', 1)
                ->value('target_operator') ?: 'gte';

            $status = $this->computeKpiStatus(
                (float) ($validated['actual'] ?? 0),
                (float) ($validated['target'] ?? 0),
                $targetOperator
            );
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

            $this->syncRejectionHasilProduksiIfNeeded(
                $entry->template,
                $validated['entry_date'],
                is_array($validated['dynamic_fields'] ?? null) ? $validated['dynamic_fields'] : []
            );

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

        DB::transaction(function () use ($entry) {
            // Delete CAPA areas and their cascaded children (problems → causes → action plans)
            $entry->capaAreas()->delete();
            // Force delete so the unique (kpi_definition_id, entry_date) constraint is freed
            $entry->forceDelete();
        });

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
        if ($template->actual_aggregation === 'formula') {
            $formula = trim((string) ($template->actual_formula ?? ''));
            if ($formula === '') {
                return null;
            }

            return $this->evaluateActualFormula($formula, $dynamicFields);
        }

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

    private function evaluateActualFormula(string $formula, array $dynamicFields): ?float
    {
        $expression = ltrim(trim($formula));
        if (str_starts_with($expression, '=')) {
            $expression = ltrim(substr($expression, 1));
        }

        if ($expression === '') {
            return null;
        }

        // Normalize dynamic field keys for case-insensitive lookup.
        $dynamicMap = [];
        foreach ($dynamicFields as $key => $value) {
            $dynamicMap[strtolower((string) $key)] = $value;
        }

        $tokens = [];
        $len = strlen($expression);
        $i = 0;
        $prevType = null; // number|ident|op|lparen|rparen

        while ($i < $len) {
            $ch = $expression[$i];

            if (ctype_space($ch)) {
                $i++;
                continue;
            }

            if ($ch === '(') {
                $tokens[] = ['type' => 'lparen'];
                $prevType = 'lparen';
                $i++;
                continue;
            }
            if ($ch === ')') {
                $tokens[] = ['type' => 'rparen'];
                $prevType = 'rparen';
                $i++;
                continue;
            }

            if ($ch === '+' || $ch === '-' || $ch === '*' || $ch === '/') {
                $isUnary = ($prevType === null || $prevType === 'op' || $prevType === 'lparen');
                if ($isUnary && $ch === '+') {
                    $i++;
                    continue; // unary plus: ignore
                }

                $op = ($isUnary && $ch === '-') ? 'u-' : $ch;
                $tokens[] = ['type' => 'op', 'value' => $op];
                $prevType = 'op';
                $i++;
                continue;
            }

            // Number: digits with optional decimal part.
            if (ctype_digit($ch) || $ch === '.') {
                $start = $i;
                $dotCount = 0;
                while ($i < $len) {
                    $c = $expression[$i];
                    if ($c === '.') {
                        $dotCount++;
                        if ($dotCount > 1) {
                            break;
                        }
                        $i++;
                        continue;
                    }
                    if (!ctype_digit($c)) {
                        break;
                    }
                    $i++;
                }
                $raw = substr($expression, $start, $i - $start);
                if ($raw === '.' || $raw === '') {
                    return null;
                }
                if (!is_numeric($raw)) {
                    return null;
                }
                $tokens[] = ['type' => 'number', 'value' => (float) $raw];
                $prevType = 'number';
                continue;
            }

            // Identifier: field key (letters/underscore, then letters/digits/underscore)
            if (ctype_alpha($ch) || $ch === '_') {
                $start = $i;
                $i++;
                while ($i < $len) {
                    $c = $expression[$i];
                    if (!(ctype_alnum($c) || $c === '_')) {
                        break;
                    }
                    $i++;
                }
                $ident = substr($expression, $start, $i - $start);
                $tokens[] = ['type' => 'ident', 'value' => $ident];
                $prevType = 'ident';
                continue;
            }

            // Unsupported character
            return null;
        }

        // Shunting-yard to RPN
        $precedence = ['u-' => 3, '*' => 2, '/' => 2, '+' => 1, '-' => 1];
        $rightAssoc = ['u-' => true];

        $output = [];
        $ops = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'number' || $token['type'] === 'ident') {
                $output[] = $token;
                continue;
            }

            if ($token['type'] === 'op') {
                $op1 = $token['value'];
                if (!isset($precedence[$op1])) {
                    return null;
                }

                while (!empty($ops)) {
                    $top = end($ops);
                    if (($top['type'] ?? null) !== 'op') {
                        break;
                    }
                    $op2 = $top['value'];
                    $p1 = $precedence[$op1];
                    $p2 = $precedence[$op2] ?? null;
                    if ($p2 === null) {
                        break;
                    }

                    $isRight = $rightAssoc[$op1] ?? false;
                    if ((!$isRight && $p1 <= $p2) || ($isRight && $p1 < $p2)) {
                        $output[] = array_pop($ops);
                        continue;
                    }
                    break;
                }

                $ops[] = $token;
                continue;
            }

            if ($token['type'] === 'lparen') {
                $ops[] = $token;
                continue;
            }

            if ($token['type'] === 'rparen') {
                $found = false;
                while (!empty($ops)) {
                    $top = array_pop($ops);
                    if (($top['type'] ?? null) === 'lparen') {
                        $found = true;
                        break;
                    }
                    $output[] = $top;
                }
                if (!$found) {
                    return null;
                }
                continue;
            }

            return null;
        }

        while (!empty($ops)) {
            $top = array_pop($ops);
            if (($top['type'] ?? null) === 'lparen' || ($top['type'] ?? null) === 'rparen') {
                return null;
            }
            $output[] = $top;
        }

        // Evaluate RPN
        $stack = [];
        foreach ($output as $token) {
            if ($token['type'] === 'number') {
                $stack[] = (float) $token['value'];
                continue;
            }

            if ($token['type'] === 'ident') {
                $key = strtolower((string) $token['value']);
                if (!array_key_exists($key, $dynamicMap) || !is_numeric($dynamicMap[$key])) {
                    return null;
                }
                $stack[] = (float) $dynamicMap[$key];
                continue;
            }

            if ($token['type'] === 'op') {
                $op = $token['value'];
                if ($op === 'u-') {
                    if (count($stack) < 1) {
                        return null;
                    }
                    $a = array_pop($stack);
                    $stack[] = -$a;
                    continue;
                }

                if (count($stack) < 2) {
                    return null;
                }
                $b = array_pop($stack);
                $a = array_pop($stack);

                switch ($op) {
                    case '+':
                        $stack[] = $a + $b;
                        break;
                    case '-':
                        $stack[] = $a - $b;
                        break;
                    case '*':
                        $stack[] = $a * $b;
                        break;
                    case '/':
                        if ($b == 0.0) {
                            return null;
                        }
                        $stack[] = $a / $b;
                        break;
                    default:
                        return null;
                }
                continue;
            }

            return null;
        }

        if (count($stack) !== 1) {
            return null;
        }

        return (float) $stack[0];
    }

    private function enrichDynamicFieldsForTemplate(KpiTemplate $template, array $dynamicFields): array
    {
        $templateCode = (string) ($template->code ?? '');
        if ($templateCode !== self::TEMPLATE_CNC_WASTE && $templateCode !== self::TEMPLATE_PD_MP_OT) {
            return $dynamicFields;
        }

        $getNum = function (string $key) use ($dynamicFields): ?float {
            if (!array_key_exists($key, $dynamicFields)) return null;
            $val = $dynamicFields[$key];
            if ($val === null || $val === '') return null;
            if (!is_numeric($val)) return null;
            return (float) $val;
        };

        if ($templateCode === self::TEMPLATE_CNC_WASTE) {
            $cb1 = $getNum('cb1');
            $cb2 = $getNum('cb2');
            $cb3 = $getNum('cb3');
            $cb4 = $getNum('cb4');
            $hasilProduksi = $getNum('hasil_produksi');

            $hasAnyWaste = ($cb1 !== null) || ($cb2 !== null) || ($cb3 !== null) || ($cb4 !== null);
            if ($hasAnyWaste) {
                $totalWasteKg = (float) (($cb1 ?? 0.0) + ($cb2 ?? 0.0) + ($cb3 ?? 0.0) + ($cb4 ?? 0.0));
                $dynamicFields['total_waste_kg'] = $totalWasteKg;

                if ($hasilProduksi !== null && $hasilProduksi > 0) {
                    $dynamicFields['total_waste_pct'] = ($totalWasteKg / $hasilProduksi) * 100.0;
                }
            }

            $moneyKeys = ['d6', 'd7', 'd8', 'd9', 'd11', 'd12', 'd13'];
            $hasAnyMoney = false;
            $copq = 0.0;
            foreach ($moneyKeys as $key) {
                $val = $getNum($key);
                if ($val === null) continue;
                $hasAnyMoney = true;
                $copq += $val;
            }
            if ($hasAnyMoney) {
                $dynamicFields['copq_material'] = $copq;
            }
        }

        if ($templateCode === self::TEMPLATE_PD_MP_OT) {
            $chargeKeys = ['ot_charge_pd1', 'ot_charge_pd2', 'ot_charge_pd3', 'ot_charge_pd4', 'ot_charge_pd5'];
            $hasAnyCharge = false;
            $totalCharge = 0.0;
            foreach ($chargeKeys as $key) {
                $val = $getNum($key);
                if ($val === null) continue;
                $hasAnyCharge = true;
                $totalCharge += $val;
            }
            if ($hasAnyCharge) {
                $dynamicFields['total_ot_charge'] = $totalCharge;
            }

            $salesAmount = $getNum('sales_amount');
            $targetSales = $getNum('target_sales');
            if ($salesAmount !== null && $targetSales !== null && $targetSales > 0) {
                $dynamicFields['achievement_pct'] = ($salesAmount / $targetSales) * 100.0;
            }
        }

        return $dynamicFields;
    }

    private function validateRejectionHasilProduksi(KpiTemplate $template, array $dynamicFields, string $errorKey): ?string
    {
        if ((string) ($template->code ?? '') !== KpiRejectionWasteNgSync::REJECTION_TEMPLATE) {
            return null;
        }

        $hasAny = false;
        foreach (['actual_ng', 'actual_produksi', 'hasil_produksi'] as $key) {
            $val = $dynamicFields[$key] ?? null;
            if ($val !== null && $val !== '') {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            return null;
        }

        $kgRaw = $dynamicFields['hasil_produksi'] ?? null;
        if ($kgRaw === null || $kgRaw === '' || ! is_numeric($kgRaw) || (float) $kgRaw <= 0) {
            return 'Hasil Produksi (Kg) wajib diisi dan harus lebih dari 0 untuk Rejection in Proses.';
        }

        return null;
    }

    private function syncRejectionHasilProduksiIfNeeded(?KpiTemplate $template, string $entryDate, array $dynamicFields): void
    {
        if (! $template || (string) ($template->code ?? '') !== KpiRejectionWasteNgSync::REJECTION_TEMPLATE) {
            return;
        }

        KpiRejectionWasteNgSync::syncFromDynamicFields($entryDate, $dynamicFields);
    }

}
