<?php

namespace Workdo\ProductService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'tax_ids',
        'category_id',
        'description',
        'sale_price',
        'purchase_price',
        'unit',
        'image',
        'images',
        'type',
        'is_active',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tax_ids' => 'array',
            'images' => 'array',
            'sale_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(\Workdo\ProductService\Models\ProductServiceCategory::class, 'category_id');
    }

    public function unitRelation()
    {
        return $this->belongsTo(\Workdo\ProductService\Models\ProductServiceUnit::class, 'unit', 'id');
    }

    public function getTaxesAttribute()
    {
        $taxIds = $this->tax_ids;

        if (is_string($taxIds)) {
            $taxIds = json_decode($taxIds, true);
        }

        if (empty($taxIds) || !is_array($taxIds)) {
            return collect([]);
        }

        return \Workdo\ProductService\Models\ProductServiceTax::whereIn('id', $taxIds)->get();
    }

    public function warehouseStocks()
    {
        return $this->hasMany(\Workdo\ProductService\Models\WarehouseStock::class, 'product_id');
    }
}
