<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view departments');
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        if (!$user->can('view departments')) {
            return false;
        }

        // Admin or users with all department access can view everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Users can only view their own department
        return $user->canAccessDepartment($department->id);
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        // Only admin can create departments
        return $user->hasRole('admin') && $user->can('create departments');
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        // Only admin can update departments
        return $user->hasRole('admin') && $user->can('edit departments');
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        // Only admin can delete departments
        return $user->hasRole('admin') && $user->can('delete departments');
    }
}
