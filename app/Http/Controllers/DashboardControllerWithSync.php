<?php

namespace App\Http\Controllers;

use App\Models\KpiTemplate;
use App\Support\KpiDashboardAssets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardControllerWithSync extends DashboardController
{
    public function payload(Request $request): JsonResponse
    {
        $response = parent::payload($request);
        $data = $response->getData(true);

        return response()->json($this->enrichDashboardPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enrichDashboardPayload(array $data): array
    {
        $meta = is_array($data['kpiChartMeta'] ?? null) ? $data['kpiChartMeta'] : [];
        $templateCode = (string) ($meta['template_code'] ?? '');

        if (empty($meta['field_units']) && $templateCode !== '') {
            $template = KpiTemplate::query()->where('code', $templateCode)->first();
            if ($template) {
                $meta['field_units'] = $template->fields()->pluck('unit', 'field_key')->all();
            }
        }

        $data['kpiChartMeta'] = $meta;

        return $data;
    }

    public function index(Request $request)
    {
        $result = parent::index($request);

        if (! $result instanceof View) {
            return $result;
        }

        $html = $result->render();

        if (! str_contains($html, 'kpi-actual-target-chart')) {
            return response($html);
        }

        $headInjection = KpiDashboardAssets::headInjectionFor($html);

        if (str_contains($html, '</head>')) {
            $html = str_ireplace('</head>', $headInjection.'</head>', $html);
        } else {
            $html = str_ireplace('</body>', $headInjection.'</body>', $html);
        }

        return response($html);
    }
}
