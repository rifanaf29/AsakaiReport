<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get all KPI templates for this department.
     */
    public function kpiTemplates(): HasMany
    {
        return $this->hasMany(KpiTemplate::class);
    }

    /**
     * Get active KPI templates only.
     */
    public function activeKpiTemplates(): HasMany
    {
        return $this->kpiTemplates()->where('is_active', true)->orderBy('sort_order');
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
