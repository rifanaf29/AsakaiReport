<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiMonthlyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_template_id',
        'department_id',
        'kpi_definition_id',
        'target_year',
        'target_month',
        'target_value',
        'target_operator',
        'target_unit',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'target_year' => 'integer',
        'target_month' => 'integer',
        'target_value' => 'decimal:2',
    ];

    /**
     * Get the template this target belongs to.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    /**
     * KPI definition (department KPI instance) this target belongs to.
     */
    public function kpiDefinition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id');
    }

    /**
     * Get the department this target belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created this target.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get target for a specific template and year.
     */
    public static function getTargetForYear(int $templateId, int $departmentId, int $year): ?float
    {
        $target = self::where('kpi_template_id', $templateId)
            ->where('department_id', $departmentId)
            ->where('target_year', $year)
            ->where('target_month', 1)
            ->first();

        return $target ? (float) $target->target_value : null;
    }

    /**
     * Backwards-compatible helper: date is accepted, but only the year is used.
     */
    public static function getTargetForDate(int $templateId, int $departmentId, string $date): ?float
    {
        $date = \Carbon\Carbon::parse($date);
        return self::getTargetForYear($templateId, $departmentId, (int) $date->year);
    }

    /**
     * Get formatted year label.
     */
    public function getYearNameAttribute(): string
    {
        return (string) $this->target_year;
    }
}
