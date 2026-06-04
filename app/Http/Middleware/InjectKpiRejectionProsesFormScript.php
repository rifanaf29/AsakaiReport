<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectKpiRejectionProsesFormScript
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->routeIs('kpi.entries.create', 'kpi.entries.edit')) {
            return $response;
        }

        if (!$response instanceof Response || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || str_contains($content, 'kpi-rejection-proses-form.js')) {
            return $response;
        }

        $scriptSrc = asset('js/kpi-rejection-proses-form.js');
        $version = @filemtime(public_path('js/kpi-rejection-proses-form.js')) ?: time();
        $tag = '<script src="'.e($scriptSrc).'?v='.$version.'" defer></script>';

        if (str_contains($content, '</body>')) {
            $content = str_ireplace('</body>', $tag.'</body>', $content);
        } else {
            $content .= $tag;
        }

        $response->setContent($content);

        return $response;
    }
}
