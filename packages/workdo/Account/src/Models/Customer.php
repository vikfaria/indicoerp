<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_code',
        'company_name',
        'contact_person_name',
        'contact_person_email',
        'contact_person_mobile',
        'tax_number',
        'payment_terms',
        'billing_address',
        'shipping_address',
        'same_as_billing',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'same_as_billing' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = self::generateCustomerCode();
            }
        });
    }

    public static function generateCustomerCode()
    {
        if (auth()->check()) {
            $lastCustomer = static::where('customer_code', 'like', 'CUST-%')
                ->where('created_by', creatorId())
                ->orderBy('customer_code', 'desc')
                ->first();

            if ($lastCustomer) {
                $lastNumber = (int) substr($lastCustomer->customer_code, 5);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
        } else {
            $lastCustomer = static::orderBy('id', 'desc')->first();
            $nextNumber = $lastCustomer ? (int) substr($lastCustomer->customer_code, 5) + 1 : 1;
        }

        return 'CUST-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
