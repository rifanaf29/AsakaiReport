<?php

namespace App\Policies;

use App\Models\KpiEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KpiEntryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any kpi entries.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view kpi');
    }

    /**
     * Determine whether the user can view the kpi entry.
     */
    public function view(User $user, KpiEntry $kpiEntry): bool
    {
        if (!$user->can('view kpi')) {
            return false;
        }

        // Admin or users with all department access can view everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Check if user can access this department
        return $user->canAccessDepartment($kpiEntry->department_id);
    }

    /**
     * Determine whether the user can create kpi entries.
     */
    public function create(User $user): bool
    {
        return $user->can('create kpi');
    }

    /**
     * Determine whether the user can update the kpi entry.
     */
    public function update(User $user, KpiEntry $kpiEntry): bool
    {
        if (!$user->can('edit kpi')) {
            return false;
        }

        // Admin or users with all department access can edit everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Check if user can access this department
        return $user->canAccessDepartment($kpiEntry->department_id);
    }

    /**
     * Determine whether the user can delete the kpi entry.
     */
    public function delete(User $user, KpiEntry $kpiEntry): bool
    {
        if (!$user->can('delete kpi')) {
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
        return $user->canAccessDepartment($kpiEntry->department_id);
    }

    /**
     * Determine whether the user can lock/unlock the kpi entry.
     */
    public function lock(User $user, KpiEntry $kpiEntry): bool
    {
        if (!$user->can('lock kpi')) {
            return false;
        }

        // Only admin and manager can lock
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            return false;
        }

        // Admin can lock anything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return true;
        }

        // Manager can only lock entries from their department
        return $user->canAccessDepartment($kpiEntry->department_id);
    }
}
