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
        'problem_category',
        'severity',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the CAPA area that owns this problem.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(CapaArea::class, 'capa_area_id');
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
     * Scope by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('problem_category', $category);
    }
}
