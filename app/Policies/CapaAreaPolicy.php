<?php

namespace App\Policies;

use App\Models\CapaArea;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CapaAreaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any capa areas.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view capa');
    }

    /**
     * Determine whether the user can view the capa area.
     */
    public function view(User $user, CapaArea $capaArea): bool
    {
        if (!$user->can('view capa')) {
            return false;
        }

        // Admin or users with all department access can view everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Check if user can access this department
        return $user->canAccessDepartment($capaArea->department_id);
    }

    /**
     * Determine whether the user can create capa areas.
     */
    public function create(User $user): bool
    {
        return $user->can('create capa');
    }

    /**
     * Determine whether the user can update the capa area.
     */
    public function update(User $user, CapaArea $capaArea): bool
    {
        if (!$user->can('edit capa')) {
            return false;
        }

        // Admin or users with all department access can edit everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Check if user can access this department
        return $user->canAccessDepartment($capaArea->department_id);
    }

    /**
     * Determine whether the user can delete the capa area.
     */
    public function delete(User $user, CapaArea $capaArea): bool
    {
        if (!$user->can('delete capa')) {
            return false;
        }

        // Only admin or manager can delete
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            return false;
        }

        // Admin can delete anything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Manager can only delete from their department
        return $user->canAccessDepartment($capaArea->department_id);
    }

    /**
     * Determine whether the user can assign CAPA action plans.
     */
    public function assign(User $user, CapaArea $capaArea): bool
    {
        if (!$user->can('assign capa')) {
            return false;
        }

        // Admin or users with all department access can assign anything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Check if user can access this department
        return $user->canAccessDepartment($capaArea->department_id);
    }
}
