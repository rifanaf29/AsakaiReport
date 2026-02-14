<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\KpiEntry;
use App\Observers\KpiEntryObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register KPI Entry Observer to enforce business rules
        KpiEntry::observe(KpiEntryObserver::class);
    }
}
