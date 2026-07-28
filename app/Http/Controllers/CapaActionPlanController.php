<?php

namespace App\Http\Controllers;

use App\Models\CapaActionPlan;
use App\Models\CapaCause;
use App\Models\CapaProblem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaActionPlanController extends Controller
{
    /** Values supported by the capa_action_plans.status enum. */
    private const STATUSES = 'open,progress,close';

    /**
     * Show the form for creating a new action plan. Plans hang off a cause, not a problem,
     * so the form picks a root cause (optionally narrowed by ?problem_id=).
     */
    public function create(Request $request)
    {
        Gate::authorize('create capa');

        $problemId = $request->get('problem_id');
        $problem = null;
        $user = auth()->user();

        if ($problemId) {
            $problem = CapaProblem::with(['area', 'causes.actionPlans'])->findOrFail($problemId);

            if (!$user->canAccessDepartment($problem->area->department_id)) {
                abort(403);
            }

            if ($problem->status === 'Closed') {
                return redirect()->route('capa.problems.show', $problem)
                    ->with('error', 'Cannot add action plans to a closed problem.');
            }
        }

        $causes = CapaCause::with(['problem.area', 'problem.causes.actionPlans'])
            ->when($problem, fn ($q) => $q->where('capa_problem_id', $problem->id))
            ->when(!$user->can_access_all_departments, function ($q) use ($user) {
                return $q->whereHas('problem.area', function ($a) use ($user) {
                    $a->where('department_id', $user->department_id);
                });
            })
            ->latest('id')
            ->get()
            ->reject(fn (CapaCause $cause) => $cause->problem?->status === 'Closed')
            ->values();

        $selectedCauseId = $request->get('cause_id');

        return view('capa.action-plans.create', compact('causes', 'problem', 'selectedCauseId'));
    }

    /**
     * Store a newly created action plan.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'capa_cause_id' => 'required|exists:capa_causes,id',
            'description' => 'required|string|max:1000',
            'person_in_charge' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'nullable|in:' . self::STATUSES,
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $cause = CapaCause::with('problem.area')->findOrFail($validated['capa_cause_id']);
        $problem = $cause->problem;
        $user = auth()->user();

        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return $this->respond($request, $problem, 'Cannot add action plans to a closed problem.', 'error');
        }

        CapaActionPlan::create([
            'capa_cause_id' => $cause->id,
            'description' => $validated['description'],
            'person_in_charge' => $validated['person_in_charge'],
            'due_date' => $validated['due_date'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'progress_percentage' => 0,
            'sort_order' => $cause->actionPlans()->count(),
            'created_by' => $user->id,
        ]);

        return $this->respond($request, $problem, 'Action plan added successfully.');
    }

    /**
     * Display the specified action plan.
     */
    public function show(CapaActionPlan $actionPlan)
    {
        Gate::authorize('view capa');

        $user = auth()->user();
        $actionPlan->load(['cause.problem.area.department', 'creator']);

        if (!$user->canAccessDepartment($actionPlan->cause->problem->area->department_id)) {
            abort(403);
        }

        return view('capa.action-plans.show', compact('actionPlan'));
    }

    /**
     * Show the form for editing the specified action plan.
     */
    public function edit(CapaActionPlan $actionPlan)
    {
        Gate::authorize('edit capa');

        $user = auth()->user();
        $actionPlan->load('cause.problem.area');
        if (!$user->canAccessDepartment($actionPlan->cause->problem->area->department_id)) {
            abort(403);
        }

        if ($actionPlan->cause->problem->status === 'Closed') {
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
        $actionPlan->load('cause.problem.area');
        $problem = $actionPlan->cause->problem;

        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        // A problem reads as "Closed" only while every one of its plans is closed. Reopening a
        // plan is therefore allowed; any other edit stays blocked so a finished CAPA is frozen.
        if ($problem->status === 'Closed' && $request->input('status') === 'close') {
            return $this->respond($request, $problem, 'Cannot edit action plans of a closed problem. Reopen the plan first.', 'error');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'person_in_charge' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:' . self::STATUSES,
            'keterangan' => 'nullable|string|max:1000',
            'completed_date' => 'nullable|date',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        // Stamp the completion date the first time a plan is closed.
        if ($validated['status'] === 'close' && empty($validated['completed_date']) && !$actionPlan->completed_date) {
            $validated['completed_date'] = now();
        }

        if ($validated['status'] !== 'close') {
            $validated['completed_date'] = null;
        }

        $validated['updated_by'] = $user->id;

        $actionPlan->update($validated);

        return $this->respond($request, $problem, 'Action plan updated successfully.');
    }

    /**
     * Remove the specified action plan.
     */
    public function destroy(Request $request, CapaActionPlan $actionPlan)
    {
        Gate::authorize('delete capa');

        $user = auth()->user();
        $actionPlan->load('cause.problem.area');
        $problem = $actionPlan->cause->problem;

        if (!$user->canAccessDepartment($problem->area->department_id)) {
            abort(403);
        }

        if ($problem->status === 'Closed') {
            return $this->respond($request, $problem, 'Cannot delete action plans of a closed problem.', 'error');
        }

        $actionPlan->delete();

        return $this->respond($request, $problem, 'Action plan deleted successfully.');
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
