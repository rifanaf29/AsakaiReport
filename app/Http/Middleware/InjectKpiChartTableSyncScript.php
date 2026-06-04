<?php

namespace App\Http\Middleware;

use App\Support\KpiDashboardAssets;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectKpiChartTableSyncScript
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('dashboard') && ! $request->routeIs('dashboard.payload')) {
            return $response;
        }

        if ($request->routeIs('dashboard.payload')) {
            return $response;
        }

        if (! $response instanceof Response || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, 'kpi-actual-target-chart')) {
            return $response;
        }

        $headInjection = KpiDashboardAssets::headInjectionFor($content);

        if (str_contains($content, '</head>')) {
            $content = str_ireplace('</head>', $headInjection.'</head>', $content);
        } else {
            $content = str_ireplace('</body>', $headInjection.'</body>', $content);
        }
        $response->setContent($content);

        return $response;
    }
}
