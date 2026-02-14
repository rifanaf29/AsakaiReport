<?php

namespace App\Http\Controllers;

use App\Models\CapaActionPlan;
use App\Models\CapaProblem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaActionPlanController extends Controller
{
    /**
     * Show the form for creating a new action plan.
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
                    ->with('error', 'Cannot add action plans to a closed problem.');
            }
        }

        $user = auth()->user();
        $problems = CapaProblem::where('status', '!=', 'Closed')
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->orderBy('problem_date', 'desc')
            ->get();

        return view('capa.action-plans.create', compact('problems', 'problem'));
    }

    /**
     * Store a newly created action plan.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'capa_problem_id' => 'required|exists:capa_problems,id',
            'action_type' => 'required|in:Corrective,Preventive',
            'action_description' => 'required|string|max:1000',
            'responsible_person' => 'required|string|max:255',
            'due_date' => 'required|date',
            'completion_criteria' => 'nullable|string|max:500',
        ]);

        // Verify problem access
        $problem = CapaProblem::findOrFail($validated['capa_problem_id']);
        $user = auth()->user();

        if (!$user->canAccessDepartment($problem->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $problem)
                ->with('error', 'Cannot add action plans to a closed problem.');
        }

        $actionPlan = CapaActionPlan::create([
            'capa_problem_id' => $validated['capa_problem_id'],
            'action_type' => $validated['action_type'],
            'action_description' => $validated['action_description'],
            'responsible_person' => $validated['responsible_person'],
            'due_date' => $validated['due_date'],
            'status' => 'Pending',
            'completion_criteria' => $validated['completion_criteria'],
            'created_by' => $user->id,
        ]);

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'Action plan added successfully.');
    }

    /**
     * Display the specified action plan.
     */
    public function show(CapaActionPlan $actionPlan)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($actionPlan->problem->department_id)) {
            abort(403);
        }

        $actionPlan->load(['problem.area', 'problem.department', 'creator']);

        return view('capa.action-plans.show', compact('actionPlan'));
    }

    /**
     * Show the form for editing the specified action plan.
     */
    public function edit(CapaActionPlan $actionPlan)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($actionPlan->problem->department_id)) {
            abort(403);
        }

        if ($actionPlan->problem->status === 'Closed') {
            return redirect()->route('capa.action-plans.show', $actionPlan)
                ->with('error', 'Cannot edit action plans of a closed problem.');
        }

        return view('capa.action-plans.edit', compact('actionPlan'));
    }

    /**
     * Update the specified action plan.
     */
    public function update(Request $request, CapaActionPlan $actionPlan)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($actionPlan->problem->department_id)) {
            abort(403);
        }

        if ($actionPlan->problem->status === 'Closed') {
            return redirect()->route('capa.action-plans.show', $actionPlan)
                ->with('error', 'Cannot edit action plans of a closed problem.');
        }

        $validated = $request->validate([
            'action_type' => 'required|in:Corrective,Preventive',
            'action_description' => 'required|string|max:1000',
            'responsible_person' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:Pending,In Progress,Completed,Cancelled',
            'completion_date' => 'nullable|date',
            'completion_notes' => 'nullable|string|max:1000',
            'completion_criteria' => 'nullable|string|max:500',
        ]);

        // Set completion date if status is completed and no date provided
        if ($validated['status'] === 'Completed' && empty($validated['completion_date'])) {
            $validated['completion_date'] = now();
        }

        $actionPlan->update($validated);

        return redirect()->route('capa.problems.show', $actionPlan->problem)
            ->with('success', 'Action plan updated successfully.');
    }

    /**
     * Remove the specified action plan.
     */
    public function destroy(CapaActionPlan $actionPlan)
    {
        Gate::authorize('delete capa');

        $user = auth()->user();
        if (!$user->canAccessDepartment($actionPlan->problem->department_id)) {
            abort(403);
        }

        if ($actionPlan->problem->status === 'Closed') {
            return redirect()->route('capa.problems.show', $actionPlan->problem)
                ->with('error', 'Cannot delete action plans of a closed problem.');
        }

        $problem = $actionPlan->problem;
        $actionPlan->delete();

        return redirect()->route('capa.problems.show', $problem)
            ->with('success', 'Action plan deleted successfully.');
    }
}
