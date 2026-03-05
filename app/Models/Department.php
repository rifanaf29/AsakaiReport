<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users in this department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get shared KPI templates assigned to this department.
     */
    public function sharedKpiTemplates(): BelongsToMany
    {
        return $this->belongsToMany(KpiTemplate::class, 'kpi_template_departments')
            ->withPivot(['display_name', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Get all KPI entries for this department.
     */
    public function kpiEntries(): HasMany
    {
        return $this->hasMany(KpiEntry::class);
    }

    /**
     * Get all CAPA areas for this department.
     */
    public function capaAreas(): HasMany
    {
        return $this->hasMany(CapaArea::class);
    }

    /**
     * Scope for active departments only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
