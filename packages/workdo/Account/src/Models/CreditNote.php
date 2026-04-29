<?php

namespace Workdo\Account\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;

class CreditNote extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'credit_note_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'credit_note_date',
        'customer_id',
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
        'credit_note_date' => 'date',
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
        return $this->hasMany(CreditNoteItem::class, 'credit_note_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceReturn::class, 'return_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class, 'credit_note_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (empty($creditNote->document_type)) {
                $creditNote->document_type = static::resolveCompanyDocumentPrefix('sales_return_prefix', 'NC', $creditNote->created_by);
            }

            if (empty($creditNote->document_series)) {
                $creditNote->document_series = static::resolveCompanyDocumentSeries('sales_return_series', $creditNote->created_by, $creditNote->establishment_id);
            }

            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = static::generateCreditNoteNumber(
                    $creditNote->created_by,
                    $creditNote->credit_note_date,
                    $creditNote->establishment_id
                );
            }
            if (empty($creditNote->credit_note_date)) {
                $creditNote->credit_note_date = now();
            }

            if (empty($creditNote->document_sequence)) {
                $creditNote->document_sequence = static::extractDocumentSequenceFromNumber($creditNote->credit_note_number);
            }

            if (empty($creditNote->fiscal_submission_status)) {
                $creditNote->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateCreditNoteNumber(?int $createdBy = null, mixed $creditNoteDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'credit_note_number',
            'sales_return_prefix',
            'NC',
            $createdBy,
            $creditNoteDate,
            'sales_return_series',
            $establishmentId
        );
    }
}
