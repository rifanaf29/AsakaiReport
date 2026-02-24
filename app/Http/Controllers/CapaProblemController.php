<?php

namespace App\Http\Controllers;

use App\Models\CapaProblem;
use App\Models\CapaArea;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaProblemController extends Controller
{
    /**
     * Display a listing of CAPA problems.
     */
    public function index(Request $request)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        $query = CapaProblem::with(['area.department', 'causes', 'creator']);

        // Department filter based on user access
        if (!$user->can_access_all_departments) {
            $query->whereHas('area', function($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        } elseif ($request->filled('department')) {
            $query->whereHas('area', function($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }

        // Area filter
        if ($request->filled('area')) {
            $query->where('capa_area_id', $request->area);
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('problem_description', 'like', "%{$search}%");
        }

        $problems = $query->withCount(['causes', 'actionPlans'])
                          ->latest()
                          ->paginate(15);

        $departments = $user->can_access_all_departments 
            ? Department::active()->orderBy('name')->get() 
            : collect([$user->department]);
        
        $areas = CapaArea::active()
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('area_name')->get();

        return view('capa.problems.index', compact('problems', 'departments', 'areas'));
    }

    /**
     * Show the form for creating a new CAPA problem.
     */
    public function create(Request $request)
    {
        Gate::authorize('create capa');

        $user = auth()->user();
        $areas = CapaArea::active()
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('area_name')->get();

        $selectedAreaId = $request->get('area');

        return view('capa.problems.create', compact('areas', 'selectedAreaId'));
    }

    /**
     * Store a newly created CAPA problem.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'capa_area_id' => 'required|exists:capa_areas,id',
            'problem_description' => 'required|string|max:1000',
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        // Get area and verify department access
        $area = CapaArea::findOrFail($validated['capa_area_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($area->department_id)) {
            abort(403, 'You do not have access to this department.');
        }

        $problem = CapaProblem::create([
            'capa_area_id' => $validated['capa_area_id'],
            'problem_description' => $validated['problem_description'],
            'severity' => $validated['severity'],
            'created_by' => $user->id,
        ]);

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'CAPA Problem created successfully. You can now add causes and action plans.');
    }

    /**
     * Display the specified CAPA problem.
     */
    public function show(CapaProblem $problem)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        // Load area first to access department
        $problem->load(['area.department', 'creator', 'causes', 'actionPlans']);
        
        if ($problem->area && !$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        return view('capa.problems.show', compact('problem'));
    }

    /**
     * Show the form for editing the specified CAPA problem.
     */
    public function edit(CapaProblem $problem)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        $problem->load('area');
        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $problem)
                ->with('error', 'Cannot edit a closed CAPA problem.');
        }

        $areas = CapaArea::active()
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('area_name')->get();

        return view('capa.problems.edit', compact('problem', 'areas'));
    }

    /**
     * Update the specified CAPA problem.
     */
    public function update(Request $request, CapaProblem $problem)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        $problem->load('area');
        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $problem)
                ->with('error', 'Cannot edit a closed CAPA problem.');
        }

        $validated = $request->validate([
            'capa_area_id' => 'required|exists:capa_areas,id',
            'problem_description' => 'required|string|max:1000',
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        // Verify area department access
        $area = CapaArea::findOrFail($validated['capa_area_id']);
        if (!$user->canAccessDepartment($area->department_id)) {
            abort(403, 'You do not have access to this department.');
        }

        $problem->update([
            'problem_description' => $validated['problem_description'],
            'severity' => $validated['severity'],
        ]);

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'CAPA Problem updated successfully.');
    }

    /**
     * Remove the specified CAPA problem.
     */
    public function destroy(CapaProblem $problem)
    {
        Gate::authorize('delete capa');

        $user = auth()->user();
        $problem->load('area');
        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        // Check for related causes and action plans
        if ($problem->causes()->count() > 0 || $problem->actionPlans()->count() > 0) {
            return redirect()->route('capa.problems.index')
                ->with('error', 'Cannot delete problem with existing causes or action plans.');
        }

        $problem->delete();

        return redirect()->route('capa.problems.index')
            ->with('success', 'CAPA Problem deleted successfully.');
    }

    /**
     * Generate a unique problem number.
     */
    // Removed generateProblemNumber method - problem_number field doesn't exist in current schema
}
