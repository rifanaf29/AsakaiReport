<?php

namespace App\Support;

class KpiDashboardAssets
{
    /** Inline CSS — always embedded in HTML so network PCs are not stuck on old cached overrides. */
    public static function criticalCss(): string
    {
        $v = (int) (@filemtime(public_path('js/kpi-chart-table-sync.js')) ?: time());

        return '<style id="kpi-dashboard-critical-css" data-kpi-build="'.$v.'">'
            .'#kpi-chart-table-sync{overflow-x:auto;overflow-y:visible}'
            .'#kpi-chart-table-sync-inner{display:flex;flex-direction:column;align-items:flex-start}'
            .'#kpi-chart-table-sync .kpi-chart-table-wrap{flex:0 0 auto;overflow:hidden;margin-bottom:8px;position:relative;box-sizing:border-box}'
            .'#kpi-chart-table-sync canvas#kpi-actual-target-chart{display:block!important;height:100%!important;min-height:280px!important}'
            .'#kpi-chart-table-sync #presentation-kpi-table{flex:0 0 auto;position:static!important;margin-top:0!important;overflow:visible!important;z-index:auto!important}'
            .'#kpi-chart-table-sync .kpi-chart-table-wrap{position:relative}'
            .'#kpi-chart-table-sync .kpi-chart-table-wrap canvas#kpi-actual-target-chart{display:block!important;max-width:none!important}'
            .'#kpi-chart-table-sync .kpi-html-y-axis{position:absolute;left:0;top:0;pointer-events:none;z-index:6}'
            .'#kpi-chart-table-sync .kpi-html-y-axis__tick{position:absolute;right:6px;transform:translateY(-50%);font-size:10px;color:#6b7280;white-space:nowrap}'
            .'#kpi-chart-table-sync .kpi-html-legend{position:absolute;left:8px;bottom:6px;display:flex;gap:10px 14px;font-size:11px;z-index:6;pointer-events:none}'
            .'#kpi-chart-table-sync .kpi-html-legend__item{display:inline-flex;align-items:center;gap:6px}'
            .'#kpi-chart-table-sync .kpi-html-legend__box{width:14px;height:14px;border:2px solid #6b7280;border-radius:2px}'
            .'#kpi-chart-table-sync .kpi-html-chart-title{position:absolute;top:2px;left:140px;right:8px;text-align:center;z-index:7;pointer-events:none}'
            .'#kpi-chart-table-sync .kpi-html-chart-title__text{font-size:15px;font-weight:700;color:#000;line-height:1.2}'
            .'#kpi-chart-table-sync .kpi-chart-table__field.sticky{position:static!important;left:auto!important;box-shadow:none!important}'
            .'#kpi-chart-table-sync,#kpi-chart-table-sync #presentation-kpi-table{display:block!important;visibility:visible!important}'
            .'.kpi-duplicate-table-hidden{display:none!important}'
            .'</style>';
    }

    public static function headInjectionFor(string $html): string
    {
        $out = str_contains($html, 'kpi-dashboard-critical-css') ? '' : self::criticalCss();

        if (! str_contains($html, 'kpi-dashboard-overrides.css')) {
            $cssSrc = asset('css/kpi-dashboard-overrides.css');
            $cssVersion = @filemtime(public_path('css/kpi-dashboard-overrides.css')) ?: time();
            $out .= '<link rel="stylesheet" href="'.e($cssSrc).'?v='.$cssVersion.'" id="kpi-dashboard-overrides-css">';
        }

        if (! str_contains($html, 'kpi-chart-table-sync.js')) {
            $scriptSrc = asset('js/kpi-chart-table-sync.js');
            $version = @filemtime(public_path('js/kpi-chart-table-sync.js')) ?: time();
            $out .= '<script src="'.e($scriptSrc).'?v='.$version.'" defer></script>';
        }

        if (! str_contains($html, 'kpi-dashboard-table-patch.js')) {
            $patchSrc = asset('js/kpi-dashboard-table-patch.js');
            $patchVersion = @filemtime(public_path('js/kpi-dashboard-table-patch.js')) ?: time();
            $out .= '<script src="'.e($patchSrc).'?v='.$patchVersion.'" defer></script>';
        }

        return $out;
    }
}
