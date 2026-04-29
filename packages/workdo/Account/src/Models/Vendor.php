<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_code',
        'company_name',
        'contact_person_name',
        'contact_person_email',
        'contact_person_mobile',
        'primary_email',
        'primary_mobile',
        'tax_number',
        'payment_terms',
        'currency_code',
        'credit_limit',
        'billing_address',
        'shipping_address',
        'same_as_billing',
        'is_active',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'same_as_billing' => 'boolean',
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $vendor->vendor_code = self::generateVendorCode();
            }
        });
    }

    public static function generateVendorCode()
    {
        if (auth()->check()) {
            $lastVendor = static::where('vendor_code', 'like', 'VEN-%')
                ->where('created_by', creatorId())
                ->orderBy('vendor_code', 'desc')
                ->first();

            if ($lastVendor) {
                $lastNumber = (int) substr($lastVendor->vendor_code, 4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
        } else {
            // For seeding or when no user is authenticated
            $lastVendor = static::orderBy('id', 'desc')->first();
            $nextNumber = $lastVendor ? (int) substr($lastVendor->vendor_code, 4) + 1 : 1;
        }

        return 'VEN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}