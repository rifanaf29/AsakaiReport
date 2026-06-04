<?php

namespace App\Support;

class KpiNumberFormat
{
    public static function resolveKind(?string $unit, ?string $rowLabel = null, ?string $fieldKey = null): string
    {
        $u = strtolower(trim((string) $unit));
        $label = strtolower((string) $rowLabel);
        $fk = strtolower(trim((string) $fieldKey));

        if ($u === 'ppm' || str_contains($label, '(ppm)') || preg_match('/\bppm\b/', $label)) {
            return 'ppm';
        }
        if ($u === '%' || str_contains($label, '(%)')) {
            return 'percent';
        }
        if ($u === 'kg' || str_contains($label, '(kg)') || str_contains($label, 'gram')) {
            return 'default';
        }
        if (in_array($u, ['pcs', 'pc'], true) || str_contains($label, '(pcs)') || str_contains($label, '(pc)')) {
            return 'pcs';
        }
        if ($fk !== '' && (str_ends_with($fk, '_pcs') || str_ends_with($fk, '_ng')
            || in_array($fk, ['actual_produksi', 'actual_ng', 'order_pcs', 'shortage_pcs'], true))) {
            return 'pcs';
        }

        return 'default';
    }

    public static function format($value, ?string $unit = null, ?string $rowLabel = null, ?string $fieldKey = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }

        $n = (float) $value;

        return match (self::resolveKind($unit, $rowLabel, $fieldKey)) {
            'ppm', 'pcs' => number_format((int) round($n), 0, ',', '.'),
            'percent' => self::trimTrailingZeros(number_format($n, 1, ',', '.')),
            default => self::formatDefault($n),
        };
    }

    private static function formatDefault(float $n): string
    {
        return self::trimTrailingZeros(number_format($n, 2, ',', '.'));
    }

    private static function trimTrailingZeros(string $formatted): string
    {
        return rtrim(rtrim($formatted, '0'), ',');
    }
}
