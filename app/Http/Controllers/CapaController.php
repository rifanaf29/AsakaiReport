<?php

namespace App\Http\Controllers;

use App\Models\CapaArea;
use App\Models\CapaProblem;
use App\Models\CapaCause;
use App\Models\CapaActionPlan;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CapaController extends Controller
{
    /**
     * Show the comprehensive CAPA creation form.
     */
    public function create()
    {
        Gate::authorize('create capa');

        $user = auth()->user();
        
        $departments = $user->can_access_all_departments 
            ? Department::active()->orderBy('name')->get() 
            : collect([$user->department]);

        $users = User::orderBy('name')->get();

        return view('capa.create-comprehensive', compact('departments', 'users'));
    }

    /**
     * Store a comprehensive CAPA with all related data.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'area_name' => 'required|string|max:100',
            'area_description' => 'nullable|string',
            'capa_date' => 'required|date',
            
            'problems' => 'required|array|min:1',
            'problems.*.problem_description' => 'required|string|max:1000',
            'problems.*.severity' => 'required|in:low,medium,high,critical',
            
            'problems.*.causes' => 'required|array|min:1',
            'problems.*.causes.*.cause_description' => 'required|string|max:500',
            
            'problems.*.causes.*.action_plans' => 'required|array|min:1',
            'problems.*.causes.*.action_plans.*.description' => 'required|string',
            'problems.*.causes.*.action_plans.*.person_in_charge' => 'required|string|max:255',
            'problems.*.causes.*.action_plans.*.due_date' => 'required|date',
            'problems.*.causes.*.action_plans.*.keterangan' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Verify department access
        if (!$user->canAccessDepartment($validated['department_id'])) {
            abort(403, 'You do not have access to this department.');
        }

        DB::transaction(function () use ($validated, $user) {
            // Create CAPA Area
            $capaArea = CapaArea::create([
                'department_id' => $validated['department_id'],
                'area_name' => $validated['area_name'],
                'area_description' => $validated['area_description'],
                'capa_date' => $validated['capa_date'],
                'is_mandatory' => false,
                'created_by' => $user->id,
            ]);

            // Create Problems, Causes, and Action Plans
            foreach ($validated['problems'] as $problemIndex => $problemData) {
                $problem = CapaProblem::create([
                    'capa_area_id' => $capaArea->id,
                    'problem_description' => $problemData['problem_description'],
                    'severity' => $problemData['severity'],
                    'sort_order' => $problemIndex,
                    'created_by' => $user->id,
                ]);

                foreach ($problemData['causes'] as $causeIndex => $causeData) {
                    $cause = CapaCause::create([
                        'capa_problem_id' => $problem->id,
                        'cause_description' => $causeData['cause_description'],
                        'sort_order' => $causeIndex,
                        'created_by' => $user->id,
                    ]);

                    foreach ($causeData['action_plans'] as $actionIndex => $actionData) {
                        CapaActionPlan::create([
                            'capa_cause_id' => $cause->id,
                            'description' => $actionData['description'],
                            'person_in_charge' => $actionData['person_in_charge'],
                            'due_date' => $actionData['due_date'],
                            'keterangan' => $actionData['keterangan'] ?? null,
                            'status' => 'open',
                            'progress_percentage' => 0,
                            'sort_order' => $actionIndex,
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('master.capa-areas.index')
            ->with('success', 'CAPA created successfully with all problems, causes, and action plans!');
    }
}
