<?php

namespace Workdo\ProductService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductServiceUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_name',
        'creator_id',
        'created_by',
    ];
}
