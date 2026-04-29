<?php

namespace App\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturn extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'return_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'return_date',
        'vendor_id',
        'warehouse_id',
        'original_invoice_id',
        'reason',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
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
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issuer_snapshot' => 'array',
        'counterparty_snapshot' => 'array',
        'fiscal_submitted_at' => 'datetime',
        'fiscal_validated_at' => 'datetime',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_id');
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

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'original_invoice_id');
    }

    public function debitNote(): HasMany
    {
        return $this->hasMany(DebitNote::class, 'return_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->document_type)) {
                $return->document_type = static::resolveCompanyDocumentPrefix('purchase_return_prefix', 'PR', $return->created_by);
            }

            if (empty($return->document_series)) {
                $return->document_series = static::resolveCompanyDocumentSeries('purchase_return_series', $return->created_by, $return->warehouse_id);
            }

            if (empty($return->establishment_id) && !empty($return->warehouse_id)) {
                $return->establishment_id = $return->warehouse_id;
            }

            if (empty($return->return_number)) {
                $return->return_number = static::generateReturnNumber(
                    $return->created_by,
                    $return->return_date,
                    $return->establishment_id ?? $return->warehouse_id
                );
            }

            if (empty($return->document_sequence)) {
                $return->document_sequence = static::extractDocumentSequenceFromNumber($return->return_number);
            }

            if (empty($return->fiscal_submission_status)) {
                $return->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateReturnNumber(?int $createdBy = null, mixed $returnDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'return_number',
            'purchase_return_prefix',
            'PR',
            $createdBy,
            $returnDate,
            'purchase_return_series',
            $establishmentId
        );
    }
}
