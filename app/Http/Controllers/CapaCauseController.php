<?php

namespace App\Http\Controllers;

use App\Models\CapaCause;
use App\Models\CapaProblem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaCauseController extends Controller
{
    /**
     * Show the form for creating a new cause.
     */
    public function create(Request $request)
    {
        Gate::authorize('create capa');

        $problemId = $request->get('problem_id');
        $problem = null;

        if ($problemId) {
            $problem = CapaProblem::findOrFail($problemId);
            $user = auth()->user();
            
            if (!$user->canAccessDepartment($problem->department_id)) {
                abort(403);
            }

            if ($problem->status === 'Closed') {
                return redirect()->route('capa.problems.show', $problem)
                    ->with('error', 'Cannot add causes to a closed problem.');
            }
        }

        $user = auth()->user();
        $problems = CapaProblem::where('status', '!=', 'Closed')
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('problem_date', 'desc')
            ->get();

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
            'cause_category' => 'required|in:Man,Machine,Material,Method,Environment,Other',
            'cause_description' => 'required|string|max:1000',
            'analysis_method' => 'required|in:5 Whys,Fishbone,Pareto,FMEA,Other',
            'corrective_action' => 'nullable|string|max:1000',
        ]);

        // Verify problem access
        $problem = CapaProblem::findOrFail($validated['capa_problem_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($problem->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $problem)
                ->with('error', 'Cannot add causes to a closed problem.');
        }

        $cause = CapaCause::create([
            'capa_problem_id' => $validated['capa_problem_id'],
            'cause_category' => $validated['cause_category'],
            'cause_description' => $validated['cause_description'],
            'analysis_method' => $validated['analysis_method'],
            'corrective_action' => $validated['corrective_action'],
            'created_by' => $user->id,
        ]);

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'Root cause added successfully.');
    }

    /**
     * Display the specified cause.
     */
    public function show(CapaCause $cause)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($cause->problem->department_id)) {
            abort(403);
        }

        $cause->load(['problem.area', 'problem.department', 'creator']);

        return view('capa.causes.show', compact('cause'));
    }

    /**
     * Show the form for editing the specified cause.
     */
    public function edit(CapaCause $cause)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($cause->problem->department_id)) {
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
        if (!$user->canAccessDepartment($cause->problem->department_id)) {
            abort(403);
        }

        if ($cause->problem->status === 'Closed') {
            return redirect()->route('capa.causes.show', $cause)
                ->with('error', 'Cannot edit causes of a closed problem.');
        }

        $validated = $request->validate([
            'cause_category' => 'required|in:Man,Machine,Material,Method,Environment,Other',
            'cause_description' => 'required|string|max:1000',
            'analysis_method' => 'required|in:5 Whys,Fishbone,Pareto,FMEA,Other',
            'corrective_action' => 'nullable|string|max:1000',
        ]);

        $cause->update($validated);

        return redirect()->route('capa.problems.show', $cause->problem)
            ->with('success', 'Root cause updated successfully.');
    }

    /**
     * Remove the specified cause.
     */
    public function destroy(CapaCause $cause)
    {
        Gate::authorize('delete capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($cause->problem->department_id)) {
            abort(403);
        }

        if ($cause->problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $cause->problem)
                ->with('error', 'Cannot delete causes of a closed problem.');
        }

        $problem = $cause->problem;
        $cause->delete();

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'Root cause deleted successfully.');
    }
}
