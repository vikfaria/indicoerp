<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\TerminationType;

class Termination extends Model
{
    use HasFactory;

    protected $fillable = [
        'notice_date',
        'termination_date',
        'offboarding_letter_delivered_at',
        'offboarding_assets_returned_at',
        'offboarding_access_revoked_at',
        'offboarding_final_payment_at',
        'offboarding_certificate_issued_at',
        'offboarding_inss_notified_at',
        'offboarding_migration_notified_at',
        'offboarding_archive_completed_at',
        'offboarding_completed_at',
        'offboarding_notes',
        'reason',
        'description',
        'document',
        'employee_id',
        'termination_type_id',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'termination_date' => 'date',
            'offboarding_letter_delivered_at' => 'date',
            'offboarding_assets_returned_at' => 'date',
            'offboarding_access_revoked_at' => 'date',
            'offboarding_final_payment_at' => 'date',
            'offboarding_certificate_issued_at' => 'date',
            'offboarding_inss_notified_at' => 'date',
            'offboarding_migration_notified_at' => 'date',
            'offboarding_archive_completed_at' => 'date',
            'offboarding_completed_at' => 'date',
            'document' => 'string',
        ];
    }



    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
