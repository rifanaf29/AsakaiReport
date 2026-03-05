<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A department-specific KPI instance that uses a shared template layout.
 *
 * Backed by the existing `kpi_template_departments` table.
 */
class KpiDefinition extends Model
{
    use HasFactory;

    protected $table = 'kpi_template_departments';

    protected $fillable = [
        'kpi_template_id',
        'department_id',
        'display_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(KpiEntry::class, 'kpi_definition_id');
    }

    public function yearlyTargets(): HasMany
    {
        return $this->hasMany(KpiMonthlyTarget::class, 'kpi_definition_id');
    }
}
