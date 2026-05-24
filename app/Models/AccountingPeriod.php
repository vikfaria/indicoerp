<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AccountingPeriod extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_year',
        'period_number',
        'period_name',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'close_checklist',
        'reopen_reason',
        'reopened_by',
        'reopened_at',
        'snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'close_checklist' => 'array',
            'snapshot' => 'array',
            'period_number' => 'integer',
        ];
    }

    // --- Relationships ---

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    // --- Scopes ---

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    // --- State checks ---

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isClosing(): bool
    {
        return $this->status === 'closing';
    }

    // --- Static helpers ---

    /**
     * Generate 12 monthly periods + period 13 for a fiscal year.
     */
    public static function generateForYear(int $companyId, string $fiscalYear, int $startMonth = 1): void
    {
        $monthNames = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
            4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
            7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
            10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $year = (int) $fiscalYear;

        for ($i = 1; $i <= 12; $i++) {
            $month = (($startMonth - 1 + $i - 1) % 12) + 1;
            $calendarYear = $month >= $startMonth ? $year : $year + 1;

            $startDate = sprintf('%d-%02d-01', $calendarYear, $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            static::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'period_number' => $i,
                ],
                [
                    'period_name' => $monthNames[$month] . ' ' . $calendarYear,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'open',
                    'created_by' => $companyId,
                ]
            );
        }

        // Period 13 - closing adjustments (same dates as period 12)
        $lastPeriod = static::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->where('period_number', 12)
            ->first();

        if ($lastPeriod) {
            static::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'period_number' => 13,
                ],
                [
                    'period_name' => 'Ajustamentos de Fecho ' . $fiscalYear,
                    'start_date' => $lastPeriod->start_date,
                    'end_date' => $lastPeriod->end_date,
                    'status' => 'open',
                    'created_by' => $companyId,
                ]
            );
        }
    }
}
