<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_template_id',
        'field_name',
        'field_key',
        'field_type',
        'is_required',
        'is_editable',
        'calculation_formula',
        'unit',
        'sort_order',
        'default_value',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the template that owns this field.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    /**
     * Check if this is a calculated field.
     */
    public function isCalculated(): bool
    {
        return $this->field_type === 'calculated';
    }

    /**
     * Get the label for this field (using field_name as the label).
     */
    public function getLabelAttribute(): string
    {
        return $this->field_name;
    }
}
