<?php

namespace Workdo\Contract\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ContractAttachment extends Model
{
    protected $fillable = [
        'contract_id',
        'file_name',
        'file_path',
        'uploaded_by',
        'creator_id',
        'created_by',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}