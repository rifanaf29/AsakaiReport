<?php

use App\Http\Middleware\InjectKpiChartTableSyncScript;
use App\Providers\KpiChartTableSyncServiceProvider;
use Illuminate\Contracts\Http\Kernel;

/*
| Loaded on every request when config is NOT cached.
| After "php artisan config:cache", run: ./scripts/enable-kpi-chart-sync.sh
*/
if (app()->bound(Kernel::class)) {
    /** @var Kernel $kernel */
    $kernel = app(Kernel::class);
    $web = $kernel->getMiddlewareGroups()['web'] ?? [];
    if (! in_array(InjectKpiChartTableSyncScript::class, $web, true)) {
        $kernel->appendMiddlewareToGroup('web', InjectKpiChartTableSyncScript::class);
    }

    $loaded = app()->getLoadedProviders();
    if (! ($loaded[KpiChartTableSyncServiceProvider::class] ?? false)) {
        app()->register(KpiChartTableSyncServiceProvider::class);
    }
}

return [];
