<?php

namespace Workdo\Account\Models;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class JournalEntry extends Model
{
    protected $fillable = [
        'journal_number',
        'journal_date',
        'entry_type',
        'reference_type',
        'reference_id',
        'description',
        'document_support',
        'total_debit',
        'total_credit',
        'status',
        'accounting_journal_id',
        'accounting_period_id',
        'fiscal_year',
        'period_number',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'period_number' => 'integer',
    ];

    // --- Relationships ---

    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    public function accountingJournal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    // --- State checks ---

    public function isBalanced(): bool
    {
        return $this->total_debit == $this->total_credit;
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    // --- Boot ---

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journalEntry) {
            if (empty($journalEntry->journal_number)) {
                $service = app(\App\Services\JournalNumberingService::class);
                $numbering = $service->generateNumber(
                    $journalEntry->created_by ?? creatorId(),
                    $journalEntry->journal_date?->toDateString() ?? date('Y-m-d'),
                    $journalEntry->accounting_journal_id
                );
                $journalEntry->journal_number = $numbering['journal_number'];
                $journalEntry->fiscal_year = $journalEntry->fiscal_year ?? $numbering['fiscal_year'];
                $journalEntry->period_number = $journalEntry->period_number ?? $numbering['period_number'];
                $journalEntry->accounting_period_id = $journalEntry->accounting_period_id ?? $numbering['accounting_period_id'];
            }

            // Always set fiscal_year if missing
            if (empty($journalEntry->fiscal_year) && $journalEntry->journal_date) {
                $journalEntry->fiscal_year = $journalEntry->journal_date->format('Y');
            }
        });
    }

    /**
     * Legacy journal number generation.
     * Kept for backward compatibility. New entries use JournalNumberingService.
     */
    public static function generateJournalNumber(): string
    {
        $year = date('Y');
        $lastEntry = static::where('journal_number', 'like', "JE-{$year}-%")
            ->orderBy('journal_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->journal_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "JE-{$year}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
