<?php

namespace App\Providers;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardControllerWithSync;
use App\Http\Middleware\InjectKpiChartTableSyncScript;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class KpiChartTableSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DashboardController::class, DashboardControllerWithSync::class);
    }

    public function boot(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $web = $kernel->getMiddlewareGroups()['web'] ?? [];
        if (! in_array(InjectKpiChartTableSyncScript::class, $web, true)) {
            $kernel->appendMiddlewareToGroup('web', InjectKpiChartTableSyncScript::class);
        }

        View::composer(
            ['pages.dashboard.dashboard', 'pages.dashboard.dashboard-fullscreen'],
            function ($view) {
                $view->with('includeKpiChartTableSync', true);
            }
        );
    }
}
