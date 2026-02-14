<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Department;
use App\Models\KpiEntry;
use App\Models\CapaArea;
use App\Models\CapaActionPlan;
use App\Policies\DepartmentPolicy;
use App\Policies\KpiEntryPolicy;
use App\Policies\CapaAreaPolicy;
use App\Policies\CapaActionPlanPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Department::class => DepartmentPolicy::class,
        KpiEntry::class => KpiEntryPolicy::class,
        CapaArea::class => CapaAreaPolicy::class,
        CapaActionPlan::class => CapaActionPlanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
