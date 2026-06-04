<?php

namespace App\Providers;

use App\Http\Middleware\InjectKpiRejectionProsesFormScript;
use App\Models\KpiEntry;
use App\Models\KpiTemplate;
use App\Observers\RejectionWasteNgKpiEntryObserver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class KpiRejectionProsesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        KpiEntry::observe(RejectionWasteNgKpiEntryObserver::class);

        if ($this->app->bound(Kernel::class)) {
            /** @var Kernel $kernel */
            $kernel = $this->app->make(Kernel::class);
            $web = $kernel->getMiddlewareGroups()['web'] ?? [];
            if (!in_array(InjectKpiRejectionProsesFormScript::class, $web, true)) {
                $kernel->appendMiddlewareToGroup('web', InjectKpiRejectionProsesFormScript::class);
            }
        }

        View::composer(
            [
                'pages.dashboard.dashboard',
                'pages.dashboard.dashboard-fullscreen',
            ],
            function ($view) {
                $meta = $view->getData()['kpiChartMeta'] ?? [];
                if (! is_array($meta)) {
                    return;
                }
                if (! empty($meta['field_units'])) {
                    return;
                }
                $template = $view->getData()['selectedTemplate'] ?? null;
                if (! $template instanceof KpiTemplate) {
                    $code = (string) ($meta['template_code'] ?? '');
                    if ($code !== '') {
                        $template = KpiTemplate::query()->where('code', $code)->first();
                    }
                }
                if ($template) {
                    $meta['field_units'] = $template->fields()->pluck('unit', 'field_key')->all();
                    $view->with('kpiChartMeta', $meta);
                }
            }
        );
    }
}
