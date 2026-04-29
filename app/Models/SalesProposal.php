<?php

namespace App\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesProposal extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'proposal_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'proposal_date',
        'due_date',
        'customer_id',
        'warehouse_id',
        'payment_terms',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'converted_to_invoice',
        'invoice_id',
        'notes',
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
        'proposal_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'converted_to_invoice' => 'boolean',
        'issuer_snapshot' => 'array',
        'counterparty_snapshot' => 'array',
        'fiscal_submitted_at' => 'datetime',
        'fiscal_validated_at' => 'datetime',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = ['display_status'];

    public function items(): HasMany
    {
        return $this->hasMany(SalesProposalItem::class, 'proposal_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function customerDetails(): BelongsTo
    {
        return $this->belongsTo(\Workdo\Account\Models\Customer::class, 'customer_id', 'user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && !in_array($this->status, ['accepted', 'rejected']);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'overdue';
        }
        return $this->status;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proposal) {
            if (empty($proposal->document_type)) {
                $proposal->document_type = static::resolveCompanyDocumentPrefix('sales_proposal_prefix', 'SP', $proposal->created_by);
            }

            if (empty($proposal->document_series)) {
                $proposal->document_series = static::resolveCompanyDocumentSeries('sales_proposal_series', $proposal->created_by, $proposal->warehouse_id);
            }

            if (empty($proposal->establishment_id) && !empty($proposal->warehouse_id)) {
                $proposal->establishment_id = $proposal->warehouse_id;
            }

            if (empty($proposal->proposal_number)) {
                $proposal->proposal_number = static::generateProposalNumber(
                    $proposal->created_by,
                    $proposal->proposal_date,
                    $proposal->establishment_id ?? $proposal->warehouse_id
                );
            }

            if (empty($proposal->document_sequence)) {
                $proposal->document_sequence = static::extractDocumentSequenceFromNumber($proposal->proposal_number);
            }

            if (empty($proposal->fiscal_submission_status)) {
                $proposal->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateProposalNumber(?int $createdBy = null, mixed $proposalDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'proposal_number',
            'sales_proposal_prefix',
            'SP',
            $createdBy,
            $proposalDate,
            'sales_proposal_series',
            $establishmentId
        );
    }
}
