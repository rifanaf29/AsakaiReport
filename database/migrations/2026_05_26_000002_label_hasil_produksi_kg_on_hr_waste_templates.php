<?php

use App\Models\KpiTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const TEMPLATE_CODES = [
        'TPL_HR_WASTE_GRAM',
        'TPL_HR_WASTE_NG',
        'TPL_HR_WASTE_PUNTUNGAN',
    ];

    public function up(): void
    {
        foreach (self::TEMPLATE_CODES as $code) {
            $template = KpiTemplate::query()->where('code', $code)->first();
            $template?->fields()->where('field_key', 'hasil_produksi')->update([
                'field_name' => 'Hasil Produksi (Kg)',
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::TEMPLATE_CODES as $code) {
            $template = KpiTemplate::query()->where('code', $code)->first();
            $template?->fields()->where('field_key', 'hasil_produksi')->update([
                'field_name' => 'Hasil Produksi',
            ]);
        }
    }
};
