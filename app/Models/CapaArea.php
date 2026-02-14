<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CapaArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'kpi_entry_id',
        'capa_date',
        'area_name',
        'area_description',
        'is_mandatory',
        'created_by',
    ];

    protected $casts = [
        'capa_date' => 'date',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Get the department for this CAPA area.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the linked KPI entry (if mandatory CAPA).
     */
    public function kpiEntry(): BelongsTo
    {
        return $this->belongsTo(KpiEntry::class);
    }

    /**
     * Get the user who created this CAPA area.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all problems for this area.
     */
    public function problems(): HasMany
    {
        return $this->hasMany(CapaProblem::class);
    }

    /**
     * Get all causes through problems.
     */
    public function causes(): HasManyThrough
    {
        return $this->hasManyThrough(CapaCause::class, CapaProblem::class);
    }

    /**
     * Get action plan completion percentage for this area.
     */
    public function getActionPlanCompletionPercentage(): float
    {
        $totalActions = 0;
        $completedActions = 0;

        foreach ($this->problems as $problem) {
            foreach ($problem->causes as $cause) {
                $actions = $cause->actionPlans;
                $totalActions += $actions->count();
                $completedActions += $actions->where('status', 'close')->count();
            }
        }

        return $totalActions > 0 ? round(($completedActions / $totalActions) * 100, 2) : 0;
    }

    /**
     * Check if all action plans are closed.
     */
    public function allActionPlansClosed(): bool
    {
        foreach ($this->problems as $problem) {
            foreach ($problem->causes as $cause) {
                if ($cause->actionPlans()->where('status', '!=', 'close')->exists()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Scope for mandatory CAPA only.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope for active (non-deleted) areas.
     * Since we use soft deletes, this just returns the query.
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('capa_date', [$startDate, $endDate]);
    }
}
