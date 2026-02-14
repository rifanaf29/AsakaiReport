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
        $query = CapaProblem::with(['area', 'department']);

        // Department filter based on user access
        if (!$user->can_access_all_departments) {
            $query->where('department_id', $user->department_id);
        } elseif ($request->filled('department')) {
            $query->where('department_id', $request->department);
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
            $query->where(function ($q) use ($search) {
                $q->where('problem_description', 'like', "%{$search}%")
                  ->orWhere('problem_number', 'like', "%{$search}%");
            });
        }

        $problems = $query->withCount(['causes', 'actionPlans'])
                          ->latest('problem_date')
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
            'problem_date' => 'required|date',
            'problem_description' => 'required|string|max:1000',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'reported_by' => 'required|string|max:255',
        ]);

        // Get area and verify department access
        $area = CapaArea::findOrFail($validated['capa_area_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($area->department_id)) {
            abort(403, 'You do not have access to this department.');
        }

        // Generate problem number
        $problemNumber = $this->generateProblemNumber($area->department_id);

        $problem = CapaProblem::create([
            'problem_number' => $problemNumber,
            'capa_area_id' => $validated['capa_area_id'],
            'department_id' => $area->department_id,
            'problem_date' => $validated['problem_date'],
            'problem_description' => $validated['problem_description'],
            'priority' => $validated['priority'],
            'status' => 'Open',
            'reported_by' => $validated['reported_by'],
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
        if (!$user->canAccessDepartment($problem->department_id)) {
            abort(403);
        }

        $problem->load(['area', 'department', 'creator', 'causes', 'actionPlans']);

        return view('capa.problems.show', compact('problem'));
    }

    /**
     * Show the form for editing the specified CAPA problem.
     */
    public function edit(CapaProblem $problem)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($problem->department_id)) {
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
        if (!$user->canAccessDepartment($problem->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $problem)
                ->with('error', 'Cannot edit a closed CAPA problem.');
        }

        $validated = $request->validate([
            'capa_area_id' => 'required|exists:capa_areas,id',
            'problem_date' => 'required|date',
            'problem_description' => 'required|string|max:1000',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'reported_by' => 'required|string|max:255',
            'status' => 'required|in:Open,In Progress,Resolved,Closed',
        ]);

        // Verify area department access
        $area = CapaArea::findOrFail($validated['capa_area_id']);
        if (!$user->canAccessDepartment($area->department_id)) {
            abort(403, 'You do not have access to this department.');
        }

        $problem->update($validated);

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
        if (!$user->canAccessDepartment($problem->department_id)) {
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
    private function generateProblemNumber($departmentId)
    {
        $year = date('Y');
        $dept = Department::find($departmentId);
        $deptCode = strtoupper(substr($dept->code ?? 'GEN', 0, 3));
        
        $lastProblem = CapaProblem::where('department_id', $departmentId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastProblem ? (intval(substr($lastProblem->problem_number, -4)) + 1) : 1;

        return sprintf('CAPA-%s-%s-%04d', $deptCode, $year, $sequence);
    }
}
