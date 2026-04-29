<?php

namespace Workdo\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Warehouse;

class Pos extends Model
{
    protected $fillable = [
        'sale_number',
        'customer_id',
        'warehouse_id',
        'pos_date',
        'status',
        'creator_id',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'tax_amount' => 'decimal:2',
            'pos_date' => 'date'
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosItem::class, 'pos_id');
    }

    public function payment()
    {
        return $this->hasOne(PosPayment::class, 'pos_id');
    }

    public static function generateSaleNumber()
    {
       $nextNumber = self::where('created_by', creatorId())->count() + 1;
        return '#POS' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}