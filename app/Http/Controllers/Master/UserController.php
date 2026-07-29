<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        Gate::authorize('view users');

        $query = User::with(['department', 'roles']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $users = $query->orderBy('name')->paginate(15);
        $roles = Role::all();
        $departments = Department::active()->orderBy('name')->get();

        return view('master.users.index', compact('users', 'roles', 'departments'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        Gate::authorize('create users');

        $departments = Department::active()->orderBy('name')->get();
        $roles = Role::all();

        return view('master.users.create', compact('departments', 'roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        Gate::authorize('create users');

        // Only the name is required: SSO can match an account by name alone.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8|confirmed',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|exists:roles,name',
            'can_access_all_departments' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            // No password given means the account signs in through SSO only.
            'password' => Hash::make($validated['password'] ?? Str::random(40)),
            'department_id' => $validated['department_id'] ?? null,
            'can_access_all_departments' => $request->has('can_access_all_departments'),
            'email_verified_at' => now(),
        ]);

        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return redirect()->route('master.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        Gate::authorize('view users');

        $user->load(['department', 'roles.permissions']);

        return view('master.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        Gate::authorize('edit users');

        $departments = Department::active()->orderBy('name')->get();
        $roles = Role::all();

        return view('master.users.edit', compact('user', 'departments', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('edit users');

        // Mirrors store(): an account created with a name only must stay editable.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|exists:roles,name',
            'can_access_all_departments' => 'boolean',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'can_access_all_departments' => $request->has('can_access_all_departments'),
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        // Leave existing roles alone when the form sends no role.
        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('master.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete users');

        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return redirect()->route('master.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('master.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
