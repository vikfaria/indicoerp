<?php

namespace App\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoice extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'invoice_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'invoice_date',
        'due_date',
        'vendor_id',
        'warehouse_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'debit_note_applied',
        'balance_amount',
        'status',
        'payment_terms',
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
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debit_note_applied' => 'decimal:2',
        'balance_amount' => 'decimal:2',
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
        return $this->hasMany(PurchaseInvoiceItem::class, 'invoice_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function vendorDetails(): BelongsTo
    {
        return $this->belongsTo(\Workdo\Account\Models\Vendor::class, 'vendor_id', 'user_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(\Workdo\Account\Models\VendorPaymentAllocation::class, 'invoice_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'original_invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status !== 'paid';
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

        static::creating(function ($invoice) {
            if (empty($invoice->document_type)) {
                $invoice->document_type = static::resolveCompanyDocumentPrefix('purchase_invoice_prefix', 'PI', $invoice->created_by);
            }

            if (empty($invoice->document_series)) {
                $invoice->document_series = static::resolveCompanyDocumentSeries('purchase_invoice_series', $invoice->created_by, $invoice->warehouse_id);
            }

            if (empty($invoice->establishment_id) && !empty($invoice->warehouse_id)) {
                $invoice->establishment_id = $invoice->warehouse_id;
            }

            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber(
                    $invoice->created_by,
                    $invoice->invoice_date,
                    $invoice->establishment_id ?? $invoice->warehouse_id
                );
            }

            if (empty($invoice->document_sequence)) {
                $invoice->document_sequence = static::extractDocumentSequenceFromNumber($invoice->invoice_number);
            }

            if (empty($invoice->fiscal_submission_status)) {
                $invoice->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateInvoiceNumber(?int $createdBy = null, mixed $invoiceDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'invoice_number',
            'purchase_invoice_prefix',
            'PI',
            $createdBy,
            $invoiceDate,
            'purchase_invoice_series',
            $establishmentId
        );
    }
}
