<?php

namespace Workdo\Account\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;

class DebitNote extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'debit_note_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'debit_note_date',
        'vendor_id',
        'invoice_id',
        'return_id',
        'reason',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'applied_amount',
        'balance_amount',
        'notes',
        'approved_by',
        'creator_id',
        'created_by',
        'issuer_snapshot',
        'counterparty_snapshot',
        'fiscal_submission_status',
        'fiscal_submission_reference',
        'fiscal_submitted_at',
        'fiscal_validated_at',
        'fiscal_validation_message',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'cancellation_reference',
        'rectification_reference',
    ];

    protected $casts = [
        'document_sequence' => 'integer',
        'establishment_id' => 'integer',
        'debit_note_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'issuer_snapshot' => 'array',
        'counterparty_snapshot' => 'array',
        'fiscal_submitted_at' => 'datetime',
        'fiscal_validated_at' => 'datetime',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class, 'debit_note_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'return_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(DebitNoteApplication::class, 'debit_note_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($debitNote) {
            if (empty($debitNote->document_type)) {
                $debitNote->document_type = static::resolveCompanyDocumentPrefix('purchase_return_prefix', 'ND', $debitNote->created_by);
            }

            if (empty($debitNote->document_series)) {
                $debitNote->document_series = static::resolveCompanyDocumentSeries('purchase_return_series', $debitNote->created_by, $debitNote->establishment_id);
            }

            if (empty($debitNote->debit_note_number)) {
                $debitNote->debit_note_number = static::generateDebitNoteNumber(
                    $debitNote->created_by,
                    $debitNote->debit_note_date,
                    $debitNote->establishment_id
                );
            }
            if (empty($debitNote->debit_note_date)) {
                $debitNote->debit_note_date = now();
            }

            if (empty($debitNote->document_sequence)) {
                $debitNote->document_sequence = static::extractDocumentSequenceFromNumber($debitNote->debit_note_number);
            }

            if (empty($debitNote->fiscal_submission_status)) {
                $debitNote->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateDebitNoteNumber(?int $createdBy = null, mixed $debitNoteDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'debit_note_number',
            'purchase_return_prefix',
            'ND',
            $createdBy,
            $debitNoteDate,
            'purchase_return_series',
            $establishmentId
        );
    }
}
