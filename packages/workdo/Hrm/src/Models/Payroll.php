<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'payroll_frequency',
        'pay_period_start',
        'pay_period_end',
        'pay_date',
        'notes',
        'total_gross_pay',
        'total_deductions',
        'total_net_pay',
        'total_irps',
        'total_inss_employee',
        'total_inss_employer',
        'employee_count',
        'status',
        'is_payroll_paid',
        'bank_account_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pay_period_start' => 'date',
            'pay_period_end' => 'date',
            'pay_date' => 'date',
            'total_gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_pay' => 'decimal:2',
            'total_irps' => 'decimal:2',
            'total_inss_employee' => 'decimal:2',
            'total_inss_employer' => 'decimal:2',
        ];
    }

    // Accessor for consistent relationship display
    public function getNameAttribute()
    {
        return $this->title;
    }

    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

}
