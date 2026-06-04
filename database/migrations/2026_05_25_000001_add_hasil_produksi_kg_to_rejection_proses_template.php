<?php

use App\Models\KpiTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $rejection = KpiTemplate::query()->where('code', 'TPL_PD_REJECTION_PROSES')->first();
        if ($rejection) {
            $rejection->fields()->updateOrCreate(
                ['field_key' => 'hasil_produksi'],
                [
                    'field_name' => 'Hasil Produksi',
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'Kg',
                    'sort_order' => 30,
                ]
            );
        }

        $wasteNg = KpiTemplate::query()->where('code', 'TPL_HR_WASTE_NG')->first();
        if ($wasteNg) {
            $wasteNg->fields()->updateOrCreate(
                ['field_key' => 'hasil_produksi'],
                [
                    'field_name' => 'Hasil Produksi (Kg)',
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'kg',
                    'sort_order' => 70,
                ]
            );
        }
    }

    public function down(): void
    {
        $rejection = KpiTemplate::query()->where('code', 'TPL_PD_REJECTION_PROSES')->first();
        if ($rejection) {
            $rejection->fields()->where('field_key', 'hasil_produksi')->delete();
        }
    }
};
