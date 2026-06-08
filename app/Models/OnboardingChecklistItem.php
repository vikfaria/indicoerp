<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingChecklistItem extends Model
{
    protected $fillable = [
        'onboarding_session_id',
        'onboarding_step_id',
        'company_id',
        'item_key',
        'item_label',
        'item_description',
        'item_order',
        'is_required',
        'state',
        'started_at',
        'completed_at',
        'skipped_at',
        'blocked_at',
        'skip_reason',
        'evidence',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'item_order' => 'integer',
            'is_required' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'blocked_at' => 'datetime',
            'evidence' => 'array',
            'metadata' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OnboardingSession::class, 'onboarding_session_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(OnboardingStep::class, 'onboarding_step_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForSession(Builder $query, int $sessionId): Builder
    {
        return $query->where('onboarding_session_id', $sessionId);
    }

    public function scopeForStep(Builder $query, int $stepId): Builder
    {
        return $query->where('onboarding_step_id', $stepId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('state', 'completed');
    }

    public function scopeSkipped(Builder $query): Builder
    {
        return $query->where('state', 'skipped');
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('state', 'blocked');
    }

    public function scopeNotApplicable(Builder $query): Builder
    {
        return $query->where('state', 'not_applicable');
    }

    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->state === 'completed';
    }

    public function isSkipped(): bool
    {
        return $this->state === 'skipped';
    }

    public function isBlocked(): bool
    {
        return $this->state === 'blocked';
    }

    public function isNotApplicable(): bool
    {
        return $this->state === 'not_applicable';
    }
}
