<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\Stage;
use Workdo\Lead\Models\Group;
use App\Models\User;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'pipeline_id',
        'stage_id',
        'sources',
        'products',
        'notes',
        'labels',
        'phone',
        'status',
        'is_active',
        'order',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'pipeline_id' => 'integer',
            'stage_id' => 'integer',
            'sources' => 'array',
            'products' => 'array',
            'labels' => 'array',
            'is_active' => 'boolean'
        ];
    }



    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(DealStage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function userDeals()
    {
        return $this->hasMany(UserDeal::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_deals', 'deal_id', 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(DealTask::class);
    }
    public function emails()
    {
        return $this->hasMany(DealEmail::class, 'deal_id');
    }

    public function discussions()
    {
        return $this->hasMany(DealDiscussion::class, 'deal_id');
    }

    public function calls()
    {
        return $this->hasMany(DealCall::class, 'deal_id');
    }

    public function activities()
    {
        return $this->hasMany(DealActivityLog::class, 'deal_id');
    }

    public function complete_tasks()
    {
        return $this->hasMany(DealTask::class, 'deal_id')->where('status', '=', 'Complete');
    }

    public function clientDeals()
    {
        return $this->hasMany(ClientDeal::class, 'deal_id');
    }

    public function files()
    {
        return $this->hasMany(DealFile::class, 'deal_id');
    }
}