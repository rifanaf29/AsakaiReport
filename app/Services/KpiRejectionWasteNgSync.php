<?php

namespace App\Services;

use App\Models\KpiDefinition;
use App\Models\KpiEntry;
use App\Models\KpiMonthlyTarget;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class KpiRejectionWasteNgSync
{
    public const REJECTION_TEMPLATE = 'TPL_PD_REJECTION_PROSES';

    public const WASTE_NG_TEMPLATE = 'TPL_HR_WASTE_NG';

    public const WASTE_GRAM_TEMPLATE = 'TPL_HR_WASTE_GRAM';

    public const WASTE_PUNTUNGAN_TEMPLATE = 'TPL_HR_WASTE_PUNTUNGAN';

    /** @var array<string, array{id_env: string, default_id: int, display_name: string}>> */
    private const SYNC_TARGETS = [
        self::WASTE_NG_TEMPLATE => [
            'id_env' => 'WASTE_NG_KPI_DEFINITION_ID',
            'default_id' => 34,
            'display_name' => 'Waste NG',
        ],
        self::WASTE_GRAM_TEMPLATE => [
            'id_env' => 'WASTE_GRAM_KPI_DEFINITION_ID',
            'default_id' => 33,
            'display_name' => 'Waste Gram',
        ],
        self::WASTE_PUNTUNGAN_TEMPLATE => [
            'id_env' => 'WASTE_PUNTUNGAN_KPI_DEFINITION_ID',
            'default_id' => 35,
            'display_name' => 'Waste Puntungan',
        ],
    ];

    /**
     * @return array<string, float> Y-m-d => Hasil Produksi (Kg) from Rejection in Proses entries
     */
    public static function rejectionHasilProduksiByDate(Carbon $monthStart, Carbon $monthEnd): array
    {
        $map = [];

        KpiEntry::query()
            ->whereHas('template', fn ($q) => $q->where('code', self::REJECTION_TEMPLATE))
            ->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select(['entry_date', 'dynamic_fields'])
            ->orderBy('entry_date')
            ->each(function (KpiEntry $entry) use (&$map) {
                $dynamic = is_array($entry->dynamic_fields) ? $entry->dynamic_fields : [];
                $raw = $dynamic['hasil_produksi'] ?? null;
                if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                    return;
                }

                $kg = (float) $raw;
                if ($kg <= 0) {
                    return;
                }

                $map[$entry->entry_date->format('Y-m-d')] = $kg;
            });

        return $map;
    }

    public static function syncFromDynamicFields(string $entryDate, array $dynamicFields): void
    {
        $kgRaw = $dynamicFields['hasil_produksi'] ?? null;
        if ($kgRaw === null || $kgRaw === '' || ! is_numeric($kgRaw)) {
            return;
        }

        $kg = (float) $kgRaw;
        if ($kg <= 0) {
            return;
        }

        foreach (array_keys(self::SYNC_TARGETS) as $templateCode) {
            static::syncKgToTemplate($templateCode, $entryDate, $kg);
        }
    }

    public static function syncKgToTemplate(string $templateCode, string $entryDate, float $kg): void
    {
        if ($kg <= 0) {
            return;
        }

        $definition = static::resolveDefinitionByTemplate($templateCode);
        if (! $definition?->template) {
            return;
        }

        $deptId = (int) $definition->department_id;
        $template = $definition->template;
        $year = Carbon::parse($entryDate)->year;

        $entry = KpiEntry::query()
            ->where('kpi_definition_id', $definition->id)
            ->where('department_id', $deptId)
            ->whereDate('entry_date', $entryDate)
            ->first();

        $merged = is_array($entry?->dynamic_fields) ? $entry->dynamic_fields : [];
        $merged['hasil_produksi'] = $kg;

        $targetValue = KpiMonthlyTarget::query()
            ->where('kpi_definition_id', $definition->id)
            ->where('target_year', $year)
            ->where('target_month', 1)
            ->value('target_value');

        if ($targetValue === null) {
            $targetValue = static::defaultTargetForTemplate($templateCode);
        }

        $operator = KpiMonthlyTarget::query()
            ->where('kpi_definition_id', $definition->id)
            ->where('target_year', $year)
            ->where('target_month', 1)
            ->value('target_operator') ?: 'lte';

        $actualValue = static::computeAggregatedActual($template, $merged) ?? 0.0;
        $status = static::computeStatus($actualValue, (float) $targetValue, $operator);

        if ($entry) {
            $entry->update([
                'dynamic_fields' => $merged,
                'actual' => $actualValue,
                'status' => $status,
            ]);

            return;
        }

        KpiEntry::create([
            'kpi_definition_id' => $definition->id,
            'kpi_template_id' => (int) $template->id,
            'department_id' => $deptId,
            'entry_date' => $entryDate,
            'target' => (float) $targetValue,
            'actual' => $actualValue,
            'status' => $status,
            'dynamic_fields' => $merged,
            'created_by' => auth()->id(),
        ]);
    }

    public static function resolveWasteNgDefinition(): ?KpiDefinition
    {
        return static::resolveDefinitionByTemplate(self::WASTE_NG_TEMPLATE);
    }

    public static function resolveDefinitionByTemplate(string $templateCode): ?KpiDefinition
    {
        $config = self::SYNC_TARGETS[$templateCode] ?? null;
        if ($config === null) {
            return null;
        }

        $configuredId = (int) env($config['id_env'], $config['default_id']);
        if ($configuredId > 0 && Schema::hasTable('kpi_definitions')) {
            $byId = KpiDefinition::query()
                ->with('template')
                ->where('id', $configuredId)
                ->where('is_active', 1)
                ->first();
            if ($byId?->template?->code === $templateCode) {
                return $byId;
            }
        }

        return KpiDefinition::query()
            ->with('template')
            ->where('display_name', $config['display_name'])
            ->where('is_active', 1)
            ->whereHas('template', fn ($q) => $q->where('code', $templateCode)->where('is_active', 1))
            ->first();
    }

    private static function defaultTargetForTemplate(string $templateCode): float
    {
        return match ($templateCode) {
            self::WASTE_GRAM_TEMPLATE => 970.7,
            self::WASTE_PUNTUNGAN_TEMPLATE => 42.5,
            default => 42.5,
        };
    }

    private static function computeAggregatedActual(KpiTemplate $template, array $dynamicFields): ?float
    {
        if ($template->actual_mode !== 'aggregated') {
            return null;
        }

        $keys = $template->actual_field_keys;
        if (! is_array($keys) || empty($keys)) {
            return null;
        }

        $sum = 0.0;
        $hasValue = false;
        foreach ($keys as $key) {
            $raw = $dynamicFields[$key] ?? null;
            if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                continue;
            }
            $sum += (float) $raw;
            $hasValue = true;
        }

        return $hasValue ? $sum : null;
    }

    private static function computeStatus(float $actual, float $target, string $operator): string
    {
        if ($operator === 'lte') {
            return $actual <= $target ? 'OK' : 'NG';
        }

        return $actual >= $target ? 'OK' : 'NG';
    }
}
