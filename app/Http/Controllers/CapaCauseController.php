<?php

namespace App\Http\Controllers;

use App\Models\CapaCause;
use App\Models\CapaProblem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaCauseController extends Controller
{
    /** Cause types supported by the capa_causes.cause_type column. */
    private const CAUSE_TYPES = 'Man,Machine,Material,Method,Environment';

    /**
     * Show the form for creating a new cause.
     */
    public function create(Request $request)
    {
        Gate::authorize('create capa');

        $problemId = $request->get('problem_id');
        $problem = null;
        $user = auth()->user();

        if ($problemId) {
            $problem = CapaProblem::with('area')->findOrFail($problemId);

            if (!$user->canAccessDepartment($problem->area->department_id)) {
                abort(403);
            }

            if ($problem->status === 'Closed') {
                return redirect()->route('capa.problems.show', $problem)
                    ->with('error', 'Cannot add causes to a closed problem.');
            }
        }

        $problems = $this->openProblemsFor($user);

        return view('capa.causes.create', compact('problems', 'problem'));
    }

    /**
     * Store a newly created cause.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'capa_problem_id' => 'required|exists:capa_problems,id',
            'cause_description' => 'required|string|max:1000',
            'cause_type' => 'nullable|in:' . self::CAUSE_TYPES,
        ]);

        // Verify problem access
        $problem = CapaProblem::with('area')->findOrFail($validated['capa_problem_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return $this->respond($request, $problem, 'Cannot add causes to a closed problem.', 'error');
        }

        CapaCause::create([
            'capa_problem_id' => $validated['capa_problem_id'],
            'cause_description' => $validated['cause_description'],
            'cause_type' => $validated['cause_type'] ?? null,
            'sort_order' => $problem->causes()->count(),
            'created_by' => $user->id,
        ]);

        return $this->respond($request, $problem, 'Root cause added successfully.');
    }

    /**
     * Display the specified cause.
     */
    public function show(CapaCause $cause)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        $cause->load(['problem.area.department', 'creator', 'actionPlans']);

        if (!$user->canAccessDepartment($cause->problem->area->department_id)) {
            abort(403);
        }

        return view('capa.causes.show', compact('cause'));
    }

    /**
     * Show the form for editing the specified cause.
     */
    public function edit(CapaCause $cause)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        $cause->load('problem.area');
        if (!$user->canAccessDepartment($cause->problem->area->department_id)) {
            abort(403);
        }

        if ($cause->problem->status === 'Closed') {
            return redirect()->route('capa.causes.show', $cause)
                ->with('error', 'Cannot edit causes of a closed problem.');
        }

        return view('capa.causes.edit', compact('cause'));
    }

    /**
     * Update the specified cause.
     */
    public function update(Request $request, CapaCause $cause)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        $cause->load('problem.area');
        if (!$user->canAccessDepartment($cause->problem->area->department_id)) {
            abort(403);
        }

        if ($cause->problem->status === 'Closed') {
            return $this->respond($request, $cause->problem, 'Cannot edit causes of a closed problem.', 'error');
        }

        $validated = $request->validate([
            'cause_description' => 'required|string|max:1000',
            'cause_type' => 'nullable|in:' . self::CAUSE_TYPES,
        ]);

        $cause->update([
            'cause_description' => $validated['cause_description'],
            'cause_type' => $validated['cause_type'] ?: null,
        ]);

        return $this->respond($request, $cause->problem, 'Root cause updated successfully.');
    }

    /**
     * Remove the specified cause.
     */
    public function destroy(Request $request, CapaCause $cause)
    {
        Gate::authorize('delete capa');

        $user = auth()->user();
        $cause->load('problem.area');
        if (!$user->canAccessDepartment($cause->problem->area->department_id)) {
            abort(403);
        }

        if ($cause->problem->status === 'Closed') {
            return $this->respond($request, $cause->problem, 'Cannot delete causes of a closed problem.', 'error');
        }

        $problem = $cause->problem;
        $cause->delete();

        return $this->respond($request, $problem, 'Root cause deleted successfully.');
    }

    /**
     * Problems the user may attach causes to (status is derived, so it is filtered in PHP).
     */
    private function openProblemsFor($user)
    {
        return CapaProblem::with(['area', 'causes.actionPlans'])
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->whereHas('area', function ($a) use ($user) {
                    $a->where('department_id', $user->department_id);
                });
            })
            ->latest()
            ->get()
            ->reject(fn (CapaProblem $problem) => $problem->status === 'Closed')
            ->values();
    }

    /**
     * JSON for the inline editor on the problem page, redirect for full page forms.
     */
    private function respond(Request $request, CapaProblem $problem, string $message, string $type = 'success')
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $type === 'success' ? 200 : 422);
        }

        return redirect()->route('capa.problems.show', $problem)->with($type, $message);
    }
}
