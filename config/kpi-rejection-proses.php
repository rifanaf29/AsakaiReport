<?php

use App\Providers\KpiRejectionProsesServiceProvider;

$loaded = app()->getLoadedProviders();
if (!($loaded[KpiRejectionProsesServiceProvider::class] ?? false)) {
    app()->register(KpiRejectionProsesServiceProvider::class);
}

return [
    /** HR Waste NG KPI definition id for Hasil Produksi (Kg) sync from Rejection in Proses */
    'waste_ng_definition_id' => (int) env('WASTE_NG_KPI_DEFINITION_ID', 34),
];
