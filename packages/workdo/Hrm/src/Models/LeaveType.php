<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LeaveType extends Model
{
    use HasFactory;

    public const LEGAL_CODES = [
        'annual',
        'maternity',
        'paternity',
        'adoption',
        'foster_care',
        'sick_leave',
        'bereavement',
        'marriage',
        'family_assistance',
        'union_leave',
        'work_accident',
        'public_service',
        'other',
    ];

    protected $fillable = [
        'name',
        'legal_code',
        'description',
        'max_days_per_year',
        'is_paid',
        'requires_supporting_document',
        'must_be_consecutive',
        'fixed_duration_days',
        'min_advance_notice_days',
        'pre_event_start_window_days',
        'post_event_start_offset_days',
        'allow_cash_out',
        'min_effective_rest_days',
        'color',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_supporting_document' => 'boolean',
            'must_be_consecutive' => 'boolean',
            'allow_cash_out' => 'boolean',
            'fixed_duration_days' => 'integer',
            'min_advance_notice_days' => 'integer',
            'pre_event_start_window_days' => 'integer',
            'post_event_start_offset_days' => 'integer',
            'min_effective_rest_days' => 'integer',
        ];
    }




}
