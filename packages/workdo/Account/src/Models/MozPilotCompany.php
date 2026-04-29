<?php

namespace Workdo\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MozPilotCompany extends Model
{
    protected $fillable = [
        'company_name',
        'company_nuit',
        'industry_sector',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'pilot_start_date',
        'pilot_end_date',
        'validation_result',
        'validation_signed_at',
        'validation_evidence_ref',
        'validation_notes',
        'validation_scope',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pilot_start_date' => 'date',
            'pilot_end_date' => 'date',
            'validation_signed_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
