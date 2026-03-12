<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KpiTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'target_unit',
        'actual_mode',
        'actual_aggregation',
        'actual_field_keys',
        'actual_formula',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'actual_field_keys' => 'array',
    ];

    /**
     * Get departments that use this template (shared templates).
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'kpi_template_departments')
            ->withPivot(['display_name', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Get all fields for this template.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(KpiTemplateField::class)->orderBy('sort_order');
    }

    /**
     * Get all KPI entries using this template.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(KpiEntry::class);
    }

    /**
     * Get all monthly targets for this template.
     */
    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(KpiMonthlyTarget::class);
    }

    /**
     * Get target for a specific year.
     */
    public function getTargetForYear(int $year): ?float
    {
        $target = $this->monthlyTargets()
            ->where('target_year', $year)
            ->where('target_month', 1)
            ->first();

        return $target ? (float) $target->target_value : null;
    }

    /**
     * Backwards-compatible: month is ignored (yearly targets).
     */
    public function getTargetForMonth(int $year, int $month): ?float
    {
        return $this->getTargetForYear($year);
    }

    /**
     * Scope for active templates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
