<?php

namespace App\Console\Commands;

use App\Models\KpiEntry;
use App\Services\KpiRejectionWasteNgSync;
use Illuminate\Console\Command;

class SyncRejectionHasilProduksiCommand extends Command
{
    protected $signature = 'kpi:sync-rejection-kg
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}';

    protected $description = 'Salin Hasil Produksi (Kg) dari Rejection in Proses ke Waste NG, Waste Gram, dan Waste Puntungan';

    public function handle(): int
    {
        $query = KpiEntry::query()
            ->whereHas('template', fn ($q) => $q->where('code', KpiRejectionWasteNgSync::REJECTION_TEMPLATE));

        if ($from = $this->option('from')) {
            $query->whereDate('entry_date', '>=', $from);
        }
        if ($to = $this->option('to')) {
            $query->whereDate('entry_date', '<=', $to);
        }

        $synced = 0;
        $query->orderBy('entry_date')->chunkById(100, function ($entries) use (&$synced) {
            foreach ($entries as $entry) {
                $dynamic = is_array($entry->dynamic_fields) ? $entry->dynamic_fields : [];
                $kg = $dynamic['hasil_produksi'] ?? null;
                if ($kg === null || $kg === '' || ! is_numeric($kg)) {
                    continue;
                }

                KpiRejectionWasteNgSync::syncFromDynamicFields(
                    $entry->entry_date->format('Y-m-d'),
                    $dynamic
                );
                $synced++;
            }
        });

        $this->info("Sinkron selesai: {$synced} entri Rejection → Waste NG / Waste Gram / Waste Puntungan (Kg saja).");

        return self::SUCCESS;
    }
}
