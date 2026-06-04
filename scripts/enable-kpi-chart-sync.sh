#!/usr/bin/env bash
# Aktifkan sinkron scroll KPI chart + tabel (setelah config:clear / optimize:clear).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

php artisan optimize:clear

if [ -w "resources/views/pages/dashboard/dashboard.blade.php" ]; then
  php scripts/patch-dashboard-blade-sync.php || true
fi

npx --yes esbuild resources/js/components/kpi-chart-table-sync.js --bundle --format=iife --outfile=public/js/kpi-chart-table-sync.js

echo ""
echo "Done. Verifikasi:"
echo "  php -r \"require 'vendor/autoload.php'; \\\$a=require 'bootstrap/app.php'; \\\$a->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); echo get_class(\\\$a->make('App\\\\Http\\\\Controllers\\\\DashboardController::class'));\""
echo "Harus: App\\Http\\Controllers\\DashboardControllerWithSync"
echo ""
echo "Lalu hard-refresh dashboard (Ctrl+Shift+R)."
