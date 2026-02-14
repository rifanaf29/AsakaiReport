<?php

namespace App\Policies;

use App\Models\CapaActionPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CapaActionPlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any action plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view capa');
    }

    /**
     * Determine whether the user can view the action plan.
     */
    public function view(User $user, CapaActionPlan $actionPlan): bool
    {
        if (!$user->can('view capa')) {
            return false;
        }

        // Admin or users with all department access can view everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Get department through relationships
        $departmentId = $actionPlan->cause->problem->area->department_id;

        // Check if user can access this department
        return $user->canAccessDepartment($departmentId);
    }

    /**
     * Determine whether the user can create action plans.
     */
    public function create(User $user): bool
    {
        return $user->can('create capa');
    }

    /**
     * Determine whether the user can update the action plan.
     */
    public function update(User $user, CapaActionPlan $actionPlan): bool
    {
        if (!$user->can('edit capa')) {
            return false;
        }

        // PIC can always update their own action plans
        if ($actionPlan->pic_user_id === $user->id) {
            return true;
        }

        // Admin or users with all department access can edit everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Get department through relationships
        $departmentId = $actionPlan->cause->problem->area->department_id;

        // Manager can edit action plans in their department
        return $user->hasRole('manager') && $user->canAccessDepartment($departmentId);
    }

    /**
     * Determine whether the user can delete the action plan.
     */
    public function delete(User $user, CapaActionPlan $actionPlan): bool
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

        // Get department through relationships
        $departmentId = $actionPlan->cause->problem->area->department_id;

        // Manager can only delete from their department
        return $user->canAccessDepartment($departmentId);
    }

    /**
     * Determine whether the user can close the action plan.
     */
    public function close(User $user, CapaActionPlan $actionPlan): bool
    {
        if (!$user->can('close capa')) {
            return false;
        }

        // PIC can close their own action plans
        if ($actionPlan->pic_user_id === $user->id) {
            return true;
        }

        // Admin can close anything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Get department through relationships
        $departmentId = $actionPlan->cause->problem->area->department_id;

        // Manager can close action plans in their department
        return $user->hasRole('manager') && $user->canAccessDepartment($departmentId);
    }
}
