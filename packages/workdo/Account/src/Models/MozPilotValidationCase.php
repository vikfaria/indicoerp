<?php

namespace Workdo\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MozPilotValidationCase extends Model
{
    protected $table = 'mz_pilot_validation_cases';

    protected $fillable = [
        'domain',
        'company_name',
        'company_nuit',
        'industry_sector',
        'scenario_code',
        'scenario_description',
        'result',
        'executed_at',
        'evidence_ref',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
