<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CapaActionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'capa_cause_id',
        'description',
        'pic_user_id',
        'person_in_charge',
        'due_date',
        'keterangan',
        'status',
        'completed_date',
        'progress_percentage',
        'completion_notes',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
        'progress_percentage' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the cause that owns this action plan.
     */
    public function cause(): BelongsTo
    {
        return $this->belongsTo(CapaCause::class, 'capa_cause_id');
    }

    /**
     * Get the problem through the cause (accessor).
     */
    public function getProblemAttribute(): ?CapaProblem
    {
        return $this->cause ? $this->cause->problem : null;
    }

    /**
     * Get the PIC (Person In Charge) for this action plan.
     */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * Get the user who created this action plan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this action plan.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the action plan is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'close' && $this->due_date->isPast();
    }

    /**
     * Get days until due date (negative if overdue).
     */
    public function daysUntilDue(): int
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    /**
     * Get overdue status with color indicator.
     */
    public function getOverdueStatusAttribute(): array
    {
        if ($this->status === 'close') {
            return ['status' => 'completed', 'color' => 'success'];
        }

        $daysUntilDue = $this->daysUntilDue();

        if ($daysUntilDue < 0) {
            return ['status' => 'overdue', 'color' => 'danger', 'days' => abs($daysUntilDue)];
        } elseif ($daysUntilDue <= 3) {
            return ['status' => 'warning', 'color' => 'warning', 'days' => $daysUntilDue];
        } else {
            return ['status' => 'on_track', 'color' => 'info', 'days' => $daysUntilDue];
        }
    }

    /**
     * Scope for open action plans.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for in-progress action plans.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'progress');
    }

    /**
     * Scope for closed action plans.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'close');
    }

    /**
     * Scope for overdue action plans.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'close')
            ->where('due_date', '<', Carbon::today());
    }

    /**
     * Scope for action plans due within X days.
     */
    public function scopeDueWithinDays($query, int $days)
    {
        return $query->where('status', '!=', 'close')
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays($days)]);
    }

    /**
     * Scope by PIC.
     */
    public function scopeByPic($query, int $userId)
    {
        return $query->where('pic_user_id', $userId);
    }
}
