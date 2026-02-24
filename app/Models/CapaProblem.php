<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CapaProblem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'capa_area_id',
        'problem_description',
        'severity',
        'sort_order',
        'created_by',0
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = ['problem_number'];

    /**
     * Get the CAPA area that owns this problem.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(CapaArea::class, 'capa_area_id');
    }

    /**
     * Get the department through the CAPA area (accessor).
     */
    public function getDepartmentAttribute()
    {
        return $this->area ? $this->area->department : null;
    }

    /**
     * Get the user who created this problem.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all causes for this problem.
     */
    public function causes(): HasMany
    {
        return $this->hasMany(CapaCause::class);
    }

    /**
     * Get all action plans through causes.
     */
    public function actionPlans(): HasManyThrough
    {
        return $this->hasManyThrough(
            CapaActionPlan::class,
            CapaCause::class,
            'capa_problem_id', // Foreign key on causes table
            'capa_cause_id',   // Foreign key on action_plans table
            'id',              // Local key on problems table
            'id'               // Local key on causes table
        );
    }

    /**
     * Scope by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Generate problem number dynamically based on ID and year.
     */
    public function getProblemNumberAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('Y') : date('Y');
        return sprintf('CAPA-%s-%04d', $year, $this->id);
    }

    /**
     * Get priority derived from severity.
     */
    public function getPriorityAttribute(): string
    {
        // Map severity to priority
        return match($this->severity) {
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => 'Medium',
        };
    }

    /**
     * Get status based on action plan completions.
     */
    public function getStatusAttribute(): string
    {
        // Check if all action plans are closed
        $actionPlans = $this->actionPlans;
        
        if ($actionPlans->isEmpty()) {
            return 'Open';
        }

        $allClosed = $actionPlans->every(function ($plan) {
            return $plan->status === 'close';
        });

        if ($allClosed) {
            return 'Closed';
        }

        $anyInProgress = $actionPlans->contains(function ($plan) {
            return $plan->status === 'progress';
        });

        return $anyInProgress ? 'In Progress' : 'Open';
    }
}
