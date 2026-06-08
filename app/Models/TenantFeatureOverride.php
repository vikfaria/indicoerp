<?php

namespace App\Models;

use App\Services\AssistantActivation\AssistantActivationCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeatureOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'override_type',
        'override_key',
        'limit_value',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $override): void {
            app(AssistantActivationCacheService::class)->touchCompanyVersion((int) $override->company_id);
        });

        static::deleted(function (self $override): void {
            app(AssistantActivationCacheService::class)->touchCompanyVersion((int) $override->company_id);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
