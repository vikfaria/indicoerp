<?php

namespace Workdo\Pos\Models;

use App\Models\Concerns\BuildsCompanyDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;

class Pos extends Model
{
    use BuildsCompanyDocumentNumber;

    protected $fillable = [
        'sale_number',
        'document_type',
        'document_series',
        'document_sequence',
        'establishment_id',
        'customer_id',
        'warehouse_id',
        'pos_date',
        'status',
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
        'creator_id',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'tax_amount' => 'decimal:2',
            'document_sequence' => 'integer',
            'establishment_id' => 'integer',
            'pos_date' => 'date',
            'fiscal_submitted_at' => 'datetime',
            'fiscal_validated_at' => 'datetime',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosItem::class, 'pos_id');
    }

    public function payment()
    {
        return $this->hasOne(PosPayment::class, 'pos_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $sale): void {
            $hasDocumentType = Schema::hasColumn('pos', 'document_type');
            $hasDocumentSeries = Schema::hasColumn('pos', 'document_series');
            $hasDocumentSequence = Schema::hasColumn('pos', 'document_sequence');
            $hasEstablishment = Schema::hasColumn('pos', 'establishment_id');
            $hasFiscalStatus = Schema::hasColumn('pos', 'fiscal_submission_status');

            if ($hasDocumentType && empty($sale->document_type)) {
                $sale->document_type = static::resolveCompanyDocumentPrefix('pos_invoice_prefix', 'POS', $sale->created_by);
            }

            if ($hasDocumentSeries && empty($sale->document_series)) {
                $sale->document_series = static::resolveCompanyDocumentSeries('pos_invoice_series', $sale->created_by, $sale->warehouse_id);
            }

            if ($hasEstablishment && empty($sale->establishment_id) && !empty($sale->warehouse_id)) {
                $sale->establishment_id = $sale->warehouse_id;
            }

            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateSaleNumber(
                    $sale->created_by,
                    $sale->pos_date,
                    $sale->establishment_id ?? $sale->warehouse_id
                );
            }

            if ($hasDocumentSequence && empty($sale->document_sequence)) {
                $sale->document_sequence = static::extractDocumentSequenceFromNumber($sale->sale_number);
            }

            if ($hasFiscalStatus && empty($sale->fiscal_submission_status)) {
                $sale->fiscal_submission_status = 'pending';
            }
        });
    }

    public static function generateSaleNumber(?int $createdBy = null, mixed $posDate = null, ?int $establishmentId = null): string
    {
        return static::generateCompanyDocumentNumber(
            'sale_number',
            'pos_invoice_prefix',
            'POS',
            $createdBy,
            $posDate,
            'pos_invoice_series',
            $establishmentId
        );
    }
}
