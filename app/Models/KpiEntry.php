<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kpi_template_id',
        'department_id',
        'entry_date',
        'target',
        'actual',
        'status',
        'dynamic_fields',
        'created_by',
        'updated_by',
        'notes',
        'is_locked',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'target' => 'decimal:2',
        'actual' => 'decimal:2',
        'dynamic_fields' => 'array',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the template for this entry.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    /**
     * Get the department for this entry.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created this entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this entry.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get CAPA areas linked to this KPI entry.
     */
    public function capaAreas(): HasMany
    {
        return $this->hasMany(CapaArea::class);
    }

    /**
     * Get mandatory CAPA area (when status is NG).
     */
    public function mandatoryCapaArea()
    {
        return $this->capaAreas()->where('is_mandatory', true)->first();
    }

    /**
     * Check if this entry requires CAPA.
     */
    public function requiresCapa(): bool
    {
        return $this->status === 'NG';
    }

    /**
     * Check if CAPA has been filled.
     */
    public function hasCapaFilled(): bool
    {
        return $this->capaAreas()->where('is_mandatory', true)->exists();
    }

    /**
     * Get a dynamic field value.
     */
    public function getDynamicField(string $key)
    {
        return $this->dynamic_fields[$key] ?? null;
    }

    /**
     * Set a dynamic field value.
     */
    public function setDynamicField(string $key, $value): void
    {
        $fields = $this->dynamic_fields ?? [];
        $fields[$key] = $value;
        $this->dynamic_fields = $fields;
    }

    /**
     * Scope for NG status entries.
     */
    public function scopeNgStatus($query)
    {
        return $query->where('status', 'NG');
    }

    /**
     * Scope for entries within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    /**
     * Scope for entries by department and date range.
     */
    public function scopeByDepartmentAndDateRange($query, $departmentId, $startDate, $endDate)
    {
        return $query->where('department_id', $departmentId)
            ->whereBetween('entry_date', [$startDate, $endDate]);
    }
}
