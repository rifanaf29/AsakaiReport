<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDepartmentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Admins and users with can_access_all_departments flag can access everything
        if ($user->hasRole('admin') || $user->can_access_all_departments) {
            return $next($request);
        }

        // Check if request contains department_id parameter
        $departmentId = $this->getDepartmentIdFromRequest($request);

        if ($departmentId && !$user->canAccessDepartment($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        return $next($request);
    }

    /**
     * Extract department_id from request.
     */
    private function getDepartmentIdFromRequest(Request $request): ?int
    {
        // Check query parameters
        if ($request->has('department_id')) {
            return (int) $request->input('department_id');
        }

        // Check route parameters
        if ($request->route('department')) {
            $department = $request->route('department');
            return is_object($department) ? $department->id : (int) $department;
        }

        // Check request body for API requests
        if ($request->isJson() && $request->has('department_id')) {
            return (int) $request->input('department_id');
        }

        // Check for related models (kpi_entry, capa_area, etc.)
        if ($request->route('kpiEntry')) {
            $kpiEntry = $request->route('kpiEntry');
            return is_object($kpiEntry) ? $kpiEntry->department_id : null;
        }

        if ($request->route('capaArea')) {
            $capaArea = $request->route('capaArea');
            return is_object($capaArea) ? $capaArea->department_id : null;
        }

        return null;
    }
}
