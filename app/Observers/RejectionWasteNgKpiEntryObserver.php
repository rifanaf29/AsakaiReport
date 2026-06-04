<?php

namespace App\Observers;

use App\Models\KpiEntry;
use App\Services\KpiRejectionWasteNgSync;

class RejectionWasteNgKpiEntryObserver
{
    public function saved(KpiEntry $entry): void
    {
        $entry->loadMissing('template');
        if ($entry->template?->code !== KpiRejectionWasteNgSync::REJECTION_TEMPLATE) {
            return;
        }

        $dynamic = is_array($entry->dynamic_fields) ? $entry->dynamic_fields : [];
        KpiRejectionWasteNgSync::syncFromDynamicFields(
            $entry->entry_date->format('Y-m-d'),
            $dynamic
        );
    }
}
