<?php

namespace App\Observers;

use App\Models\KpiEntry;
use App\Models\CapaArea;

class KpiEntryObserver
{
    /**
     * Handle the KpiEntry "saving" event.
     * Enforce business rule: If status changes to NG, ensure CAPA area exists.
     */
    public function saving(KpiEntry $kpiEntry): void
    {
        // Status validation will be handled in the controller
        // No need to set temporary flags here
    }

    /**
     * Handle the KpiEntry "saved" event.
     */
    public function saved(KpiEntry $kpiEntry): void
    {
        // Log NG status for tracking
        if ($kpiEntry->status === 'NG') {
            logger()->info("KPI Entry {$kpiEntry->id} has NG status", [
                'kpi_entry_id' => $kpiEntry->id,
                'department_id' => $kpiEntry->department_id,
                'entry_date' => $kpiEntry->entry_date,
            ]);
        }
    }

    /**
     * Handle the KpiEntry "updated" event.
     */
    public function updated(KpiEntry $kpiEntry): void
    {
        // If status changes from NG to OK/PENDING, optionally handle CAPA cleanup
        if ($kpiEntry->wasChanged('status') && $kpiEntry->getOriginal('status') === 'NG' && $kpiEntry->status !== 'NG') {
            // Optionally: mark mandatory CAPA areas as no longer mandatory
            // This is business decision - whether to keep CAPA data when status changes
            logger()->info("KPI Entry {$kpiEntry->id} status changed from NG", [
                'old_status' => $kpiEntry->getOriginal('status'),
                'new_status' => $kpiEntry->status,
            ]);
        }
    }

    /**
     * Handle the KpiEntry "deleting" event.
     */
    public function deleting(KpiEntry $kpiEntry): void
    {
        // Optionally prevent deletion if there are linked CAPA areas
        if ($kpiEntry->capaAreas()->exists()) {
            // In production, you might want to throw an exception or soft delete
            logger()->warning("Deleting KPI Entry {$kpiEntry->id} with linked CAPA areas");
        }
    }
}
