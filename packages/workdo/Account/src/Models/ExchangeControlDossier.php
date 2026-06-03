<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeControlDossier extends Model
{
    protected $fillable = [
        'company_id',
        'direction',
        'payment_type',
        'payment_id',
        'payment_reference',
        'operation_date',
        'counterparty_name',
        'counterparty_country',
        'currency_code',
        'amount_mzn',
        'documents',
        'required_documents',
        'missing_documents',
        'is_complete',
        'completed_at',
        'completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'operation_date' => 'date',
        'amount_mzn' => 'decimal:2',
        'documents' => 'array',
        'required_documents' => 'array',
        'missing_documents' => 'array',
        'is_complete' => 'boolean',
        'completed_at' => 'datetime',
    ];
}
