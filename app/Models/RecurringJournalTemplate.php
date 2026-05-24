<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'accounting_journal_id',
        'name',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'next_run_date',
        'last_run_date',
        'requires_approval',
        'is_active',
        'template_items',
        'total_amount',
        'executions_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_date' => 'date',
            'last_run_date' => 'date',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'template_items' => 'array',
            'total_amount' => 'decimal:2',
            'executions_count' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function accountingJournal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class);
    }

    /**
     * Check if the template is due to run.
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && $this->next_run_date->isAfter($this->end_date)) {
            return false;
        }

        return $this->next_run_date->isPast() || $this->next_run_date->isToday();
    }

    /**
     * Calculate the next run date after execution.
     */
    public function calculateNextRunDate(): string
    {
        $current = $this->next_run_date;

        return match ($this->frequency) {
            'monthly' => $current->addMonth()->toDateString(),
            'quarterly' => $current->addMonths(3)->toDateString(),
            'semi_annual' => $current->addMonths(6)->toDateString(),
            'annual' => $current->addYear()->toDateString(),
            default => $current->addMonth()->toDateString(),
        };
    }
}
