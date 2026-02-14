<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapaCause extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'capa_problem_id',
        'cause_description',
        'cause_type',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the problem that owns this cause.
     */
    public function problem(): BelongsTo
    {
        return $this->belongsTo(CapaProblem::class, 'capa_problem_id');
    }

    /**
     * Get the user who created this cause.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all action plans for this cause.
     */
    public function actionPlans(): HasMany
    {
        return $this->hasMany(CapaActionPlan::class);
    }

    /**
     * Scope by cause type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('cause_type', $type);
    }
}
