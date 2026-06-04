<?php

/**
 * Patches dashboard blade files to include KPI chart/table sync script.
 * Run as user with write access to dashboard*.blade.php:
 *   php scripts/patch-dashboard-blade-sync.php
 */

$files = [
    __DIR__ . '/../resources/views/pages/dashboard/dashboard.blade.php',
    __DIR__ . '/../resources/views/pages/dashboard/dashboard-fullscreen.blade.php',
];

$include = "                    @include('pages.dashboard.partials.kpi-chart-table-sync-script')\n";

foreach ($files as $file) {
    if (! is_readable($file)) {
        fwrite(STDERR, "Not readable: {$file}\n");
        continue;
    }
    if (! is_writable($file)) {
        fwrite(STDERR, "Not writable (run as iotgmu): {$file}\n");
        continue;
    }

    $content = file_get_contents($file);
    if (str_contains($content, 'kpi-chart-table-sync-script')) {
        echo "Already patched: {$file}\n";
        continue;
    }

    $marker = '</div>' . "\n" . '            </div>' . "\n\n" . '            <!-- CAPA Problems Table -->';
    $markerFs = '</div>' . "\n" . '            </div>' . "\n\n" . '            <script>' . "\n" . '                (function () {';

    if (str_contains($content, $markerFs)) {
        $content = str_replace(
            $markerFs,
            $include . $markerFs,
            $content,
            $count
        );
        if ($count > 0) {
            file_put_contents($file, $content);
            echo "Patched (fullscreen): {$file}\n";
            continue;
        }
    }

    $markerDash = $include . '</div>' . "\n" . '            </div>' . "\n\n" . '            <!-- CAPA Problems Table -->';
    if (str_contains($content, '</div>' . "\n" . '            </div>' . "\n\n" . '            <!-- CAPA Problems Table -->')) {
        $content = str_replace(
            '</div>' . "\n" . '            </div>' . "\n\n" . '            <!-- CAPA Problems Table -->',
            $include . '</div>' . "\n" . '            </div>' . "\n\n" . '            <!-- CAPA Problems Table -->',
            $content,
            $count
        );
        if ($count > 0) {
            file_put_contents($file, $content);
            echo "Patched (dashboard): {$file}\n";
            continue;
        }
    }

    fwrite(STDERR, "Marker not found in: {$file}\n");
}
