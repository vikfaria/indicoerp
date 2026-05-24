<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FiscalDocumentSeries extends Model
{
    protected $fillable = [
        'company_id', 'fiscal_document_type_id', 'series_code',
        'fiscal_year', 'last_sequence', 'last_hash',
        'is_active', 'valid_from', 'valid_to', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'last_sequence' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function fiscalDocumentType(): BelongsTo
    {
        return $this->belongsTo(FiscalDocumentType::class);
    }

    /**
     * Get the next sequence number (thread-safe with lock).
     */
    public function getNextSequence(): int
    {
        return DB::transaction(function () {
            $series = static::lockForUpdate()->find($this->id);
            $next = $series->last_sequence + 1;
            $series->update(['last_sequence' => $next]);
            return $next;
        });
    }

    /**
     * Update the last hash after document emission.
     */
    public function updateLastHash(string $hash): void
    {
        $this->update(['last_hash' => $hash]);
    }

    /**
     * Get the formatted document number.
     * Format: {TYPE} {SERIES}/{SEQUENCE}  e.g. FT A/1
     */
    public function formatDocumentNumber(int $sequence): string
    {
        $type = $this->fiscalDocumentType;
        return sprintf('%s %s/%d', $type->code, $this->series_code, $sequence);
    }
}
