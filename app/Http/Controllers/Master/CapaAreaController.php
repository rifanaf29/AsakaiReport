<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\CapaArea;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapaAreaController extends Controller
{
    /**
     * Display a listing of CAPA areas.
     */
    public function index(Request $request)
    {
        Gate::authorize('view capa');

        $query = CapaArea::with('department')->withCount('problems');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('area_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $areas = $query->orderBy('area_name')->paginate(15);
        $departments = Department::active()->orderBy('name')->get();

        return view('master.capa-areas.index', compact('areas', 'departments'));
    }

    /**
     * Show the form for creating a new CAPA area.
     */
    public function create()
    {
        Gate::authorize('create capa');

        $departments = Department::active()->orderBy('name')->get();

        return view('master.capa-areas.create', compact('departments'));
    }

    /**
     * Store a newly created CAPA area.
     */
    public function store(Request $request)
    {
        Gate::authorize('create capa');

        $validated = $request->validate([
            'area_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
        ]);

        CapaArea::create($validated);

        return redirect()->route('master.capa-areas.index')
            ->with('success', 'CAPA Area created successfully.');
    }

    /**
     * Display the specified CAPA area.
     */
    public function show(CapaArea $capaArea)
    {
        Gate::authorize('view capa');

        $capaArea->load(['department', 'problems' => function ($query) {
            $query->with('causes.actionPlans')->latest()->take(10);
        }]);

        return view('master.capa-areas.show', compact('capaArea'));
    }

    /**
     * Show the form for editing the specified CAPA area.
     */
    public function edit(CapaArea $capaArea)
    {
        Gate::authorize('edit capa');

        $departments = Department::active()->orderBy('name')->get();

        return view('master.capa-areas.edit', compact('capaArea', 'departments'));
    }

    /**
     * Update the specified CAPA area.
     */
    public function update(Request $request, CapaArea $capaArea)
    {
        Gate::authorize('edit capa');

        $validated = $request->validate([
            'area_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
        ]);

        $capaArea->update($validated);

        return redirect()->route('master.capa-areas.index')
            ->with('success', 'CAPA Area updated successfully.');
    }

    /**
     * Remove the specified CAPA area.
     */
    public function destroy(CapaArea $capaArea)
    {
        Gate::authorize('delete capa');

        // Check if area has problems
        if ($capaArea->problems()->count() > 0) {
            return redirect()->route('master.capa-areas.index')
                ->with('error', 'Cannot delete area with existing problems.');
        }

        $capaArea->delete();

        return redirect()->route('master.capa-areas.index')
            ->with('success', 'CAPA Area deleted successfully.');
    }
}
