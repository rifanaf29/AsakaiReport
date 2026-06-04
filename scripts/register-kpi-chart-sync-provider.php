<?php

/**
 * One-time helper: register KpiChartTableSyncServiceProvider in config/app.php
 * Run as a user that can write config/app.php:
 *   php scripts/register-kpi-chart-sync-provider.php
 */

$configPath = dirname(__DIR__) . '/config/app.php';
$config = file_get_contents($configPath);

if (str_contains($config, 'KpiChartTableSyncServiceProvider')) {
    echo "Already registered.\n";
    exit(0);
}

$needle = "App\\Providers\\JetstreamServiceProvider::class,";
$replacement = $needle . "\n        App\\Providers\\KpiChartTableSyncServiceProvider::class,";

if (! str_contains($config, $needle)) {
    fwrite(STDERR, "Could not find JetstreamServiceProvider entry in config/app.php\n");
    exit(1);
}

file_put_contents($configPath, str_replace($needle, $replacement, $config));
echo "Registered KpiChartTableSyncServiceProvider in config/app.php\n";
