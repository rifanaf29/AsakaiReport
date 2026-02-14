<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'target_unit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the department that owns this template.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
     * Get target for a specific month.
     */
    public function getTargetForMonth(int $year, int $month): ?float
    {
        $target = $this->monthlyTargets()
            ->where('target_year', $year)
            ->where('target_month', $month)
            ->first();

        return $target ? (float) $target->target_value : null;
    }

    /**
     * Scope for active templates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
