<?php

namespace Workdo\Contract\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Contract\Models\ContractType;
use Workdo\Contract\Models\ContractAttachment;
use Workdo\Contract\Models\ContractComment;
use Workdo\Contract\Models\ContractNote;
use Workdo\Contract\Models\ContractRenewal;
use Workdo\Contract\Models\ContractSignature;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'user_id',
        'value',
        'type_id',
        'start_date',
        'end_date',
        'description',
        'status',
        'source_type',
        'contract_number',
        'template_number',
        'creator_id',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->source_type)) {
                $contract->source_type = 'contract';
            }

            if ($contract->source_type === 'template' && empty($contract->template_number)) {
                $contract->template_number = self::generateNumber('template', $contract->created_by);
            } elseif ($contract->source_type === 'contract' && empty($contract->contract_number)) {
                $contract->contract_number = self::generateNumber('contract', $contract->created_by);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'value' => 'decimal:2',
            'type_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'string'
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'type_id');
    }

    public function attachments()
    {
        return $this->hasMany(ContractAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(ContractComment::class)->with('user')->latest();
    }

    public function notes()
    {
        return $this->hasMany(ContractNote::class)->with('user')->latest();
    }

    public function renewals()
    {
        return $this->hasMany(ContractRenewal::class)->with('creator')->latest();
    }

    public function signatures()
    {
        return $this->hasMany(ContractSignature::class)->with('user')->latest();
    }



    public static function generateNumber($type = 'contract', $created_by): string
    {
        $prefix = company_setting('contract_prefix', $created_by)   ?? ($type === 'template' ? 'CT' : 'CON');
        $numberField = ($type === 'template') ? 'template_number' : 'contract_number';
        $lastContract = self::where('created_by', $created_by)
            ->where('source_type', $type)
            ->where($numberField, 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->$numberField, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

}
