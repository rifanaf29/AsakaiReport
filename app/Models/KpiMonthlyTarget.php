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
        'target_year',
        'target_month',
        'target_value',
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
     * Get the user who created this target.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get target for a specific template and date.
     */
    public static function getTargetForDate(int $templateId, string $date): ?float
    {
        $date = \Carbon\Carbon::parse($date);
        
        $target = self::where('kpi_template_id', $templateId)
            ->where('target_year', $date->year)
            ->where('target_month', $date->month)
            ->first();

        return $target ? (float) $target->target_value : null;
    }

    /**
     * Get formatted month name.
     */
    public function getMonthNameAttribute(): string
    {
        return \Carbon\Carbon::create($this->target_year, $this->target_month, 1)->format('F Y');
    }
}
