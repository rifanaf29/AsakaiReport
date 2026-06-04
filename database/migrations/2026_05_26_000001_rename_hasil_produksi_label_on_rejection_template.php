<?php

use App\Models\KpiTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $rejection = KpiTemplate::query()->where('code', 'TPL_PD_REJECTION_PROSES')->first();
        if ($rejection) {
            $rejection->fields()->where('field_key', 'hasil_produksi')->update([
                'field_name' => 'Hasil Produksi (Kg)',
            ]);
        }
    }

    public function down(): void
    {
        $rejection = KpiTemplate::query()->where('code', 'TPL_PD_REJECTION_PROSES')->first();
        if ($rejection) {
            $rejection->fields()->where('field_key', 'hasil_produksi')->update([
                'field_name' => 'Hasil Produksi',
            ]);
        }
    }
};
