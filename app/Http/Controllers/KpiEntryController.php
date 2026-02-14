<?php

namespace App\Http\Controllers;

use App\Models\KpiEntry;
use App\Models\KpiTemplate;
use App\Models\Department;
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
        $query = KpiEntry::with(['template', 'department']);

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

        return view('kpi.entries.create', compact('templates', 'selectedTemplate'));
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

        return redirect()->route('kpi.entries.index')
            ->with('success', 'KPI Entry created successfully.');
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

        $entry->load(['template.fields', 'department', 'creator']);

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

        // Check if entry is locked
        if ($entry->is_locked) {
            return redirect()->route('kpi.entries.show', $entry)
                ->with('error', 'This entry is locked and cannot be edited.');
        }

        $entry->load(['template.fields' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return view('kpi.entries.edit', compact('entry'));
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

        if ($entry->is_locked) {
            return redirect()->route('kpi.entries.show', $entry)
                ->with('error', 'This entry is locked and cannot be edited.');
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'target' => 'required|numeric',
            'actual' => 'required|numeric',
            'notes' => 'nullable|string',
            'dynamic_fields' => 'nullable|array',
        ]);

        // Auto-calculate status
        $status = 'OK';
        if ($validated['actual'] < $validated['target']) {
            $status = 'NG';
        }

        $entry->update([
            'entry_date' => $validated['entry_date'],
            'target' => $validated['target'],
            'actual' => $validated['actual'],
            'status' => $status,
            'notes' => $validated['notes'],
            'dynamic_fields' => $validated['dynamic_fields'] ?? null,
        ]);

        return redirect()->route('kpi.entries.index')
            ->with('success', 'KPI Entry updated successfully.');
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

        if ($entry->is_locked) {
            return redirect()->route('kpi.entries.index')
                ->with('error', 'This entry is locked and cannot be deleted.');
        }

        $entry->delete();

        return redirect()->route('kpi.entries.index')
            ->with('success', 'KPI Entry deleted successfully.');
    }

    /**
     * Lock the specified KPI entry.
     */
    public function lock(KpiEntry $entry)
    {
        Gate::authorize('lock kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        $entry->update(['is_locked' => true]);

        return back()->with('success', 'KPI Entry locked successfully.');
    }

    /**
     * Unlock the specified KPI entry.
     */
    public function unlock(KpiEntry $entry)
    {
        Gate::authorize('unlock kpi');

        $user = auth()->user();
        if (!$user->canAccessDepartment($entry->department_id)) {
            abort(403);
        }

        $entry->update(['is_locked' => false]);

        return back()->with('success', 'KPI Entry unlocked successfully.');
    }
}
