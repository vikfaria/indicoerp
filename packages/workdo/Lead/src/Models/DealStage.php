<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Lead\Models\Pipeline;

class DealStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'pipeline_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }



    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }
}