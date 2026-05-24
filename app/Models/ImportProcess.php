<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportProcess extends Model
{
    protected $fillable = [
        'company_id', 'process_number', 'supplier_name', 'supplier_country',
        'import_date', 'customs_declaration', 'fob_value', 'fob_currency',
        'exchange_rate', 'fob_value_mzn', 'freight', 'insurance', 'cif_value',
        'customs_duties', 'customs_duty_rate', 'import_vat', 'clearance_fees',
        'other_costs', 'total_landed_cost', 'status', 'journal_entry_id',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'import_date' => 'date',
            'fob_value' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'fob_value_mzn' => 'decimal:2',
            'freight' => 'decimal:2',
            'insurance' => 'decimal:2',
            'cif_value' => 'decimal:2',
            'customs_duties' => 'decimal:2',
            'customs_duty_rate' => 'decimal:2',
            'import_vat' => 'decimal:2',
            'clearance_fees' => 'decimal:2',
            'other_costs' => 'decimal:2',
            'total_landed_cost' => 'decimal:2',
        ];
    }

    /**
     * Calculate all derived values.
     */
    public function calculateCosts(): void
    {
        $this->fob_value_mzn = round($this->fob_value * $this->exchange_rate, 2);
        $this->cif_value = $this->fob_value_mzn + $this->freight + $this->insurance;
        $this->customs_duties = round($this->cif_value * $this->customs_duty_rate / 100, 2);
        $this->import_vat = round(($this->cif_value + $this->customs_duties) * 0.16, 2); // 16% IVA
        $this->total_landed_cost = $this->cif_value + $this->customs_duties + $this->clearance_fees + $this->other_costs;
        // Note: import_vat is NOT part of landed cost if deductible
    }
}
