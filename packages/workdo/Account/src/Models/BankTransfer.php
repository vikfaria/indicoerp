<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BankTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'transfer_date',
        'from_account_id',
        'to_account_id',
        'transfer_amount',
        'transfer_charges',
        'reference_number',
        'description',
        'status',
        'journal_entry_id',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'transfer_amount' => 'decimal:2',
        'transfer_charges' => 'decimal:2'
    ];

    public function fromAccount()
    {
        return $this->belongsTo(BankAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(BankAccount::class, 'to_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public static function generateTransferNumber()
    {
        $year = date('Y');
        $month = date('m');

        $lastTransfer = self::where('transfer_number', 'like', "BT-{$year}-{$month}-%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "BT-{$year}-{$month}-{$newNumber}";
    }

    public function getTotalDebitAttribute()
    {
        return $this->transfer_amount + $this->transfer_charges;
    }
}