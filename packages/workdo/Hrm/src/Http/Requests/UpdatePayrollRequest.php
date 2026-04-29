<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:100',
            'payroll_frequency' => 'required',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after:pay_period_start',
            'pay_date' => 'required|date|after:pay_period_end',
            'notes' => 'nullable|max:1000',
            'status' => 'required',
            'is_payroll_paid' => 'nullable|in:paid,unpaid'
        ];
    }
}