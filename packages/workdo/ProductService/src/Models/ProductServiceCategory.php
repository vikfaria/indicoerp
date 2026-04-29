<?php

namespace Workdo\ProductService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'creator_id',
        'created_by',
    ];


}
