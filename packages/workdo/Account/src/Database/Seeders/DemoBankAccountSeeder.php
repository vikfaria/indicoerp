<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\BankAccount;
use Illuminate\Database\Seeder;
use Workdo\Account\Models\ChartOfAccount;


class DemoBankAccountSeeder extends Seeder
{
    public function run($userId): void
    {

        $bankAccounts = [
            [
                'account_number' => '1234567890',
                'account_name' => 'Business Checking Account',
                'bank_name' => 'Chase Bank',
                'branch_name' => 'Downtown Branch',
                'account_type' => '0',
                'payment_gateway' => 'Stripe',
                'opening_balance' => 500000.00,
                'current_balance' => 750000.00,
                'iban' => 'US64SVBKUS6S3300958879',
                'swift_code' => 'CHASUS33',
                'routing_number' => '021000021',
                'is_active' => true,
            ],
            [
                'account_number' => '9876543210',
                'account_name' => 'Savings Account',
                'bank_name' => 'Bank of America',
                'branch_name' => 'Main Street Branch',
                'account_type' => '1',
                'payment_gateway' => 'PayPal',
                'opening_balance' => 1000000.00,
                'current_balance' => 1250000.00,
                'iban' => 'US29NFCU0000000001001808',
                'swift_code' => 'BOFAUS3N',
                'routing_number' => '026009593',
                'is_active' => true,
            ],
            [
                'account_number' => '5555666677',
                'account_name' => 'Credit Line Account',
                'bank_name' => 'Wells Fargo',
                'branch_name' => 'Business Center',
                'account_type' => '2',
                'payment_gateway' => 'Square',
                'opening_balance' => 0.00,
                'current_balance' => 2500000.00,
                'iban' => 'US12WFBIUS6S',
                'swift_code' => 'WFBIUS6S',
                'routing_number' => '121000248',
                'is_active' => true,
            ],
            [
                'account_number' => '1111222233',
                'account_name' => 'Equipment Loan Account',
                'bank_name' => 'Citibank',
                'branch_name' => 'Corporate Branch',
                'account_type' => '3',
                'payment_gateway' => 'Razorpay',
                'opening_balance' => 2500000.00,
                'current_balance' => 3500000.00,
                'iban' => 'US33CITIUS33',
                'swift_code' => 'CITIUS33',
                'routing_number' => '021000089',
                'is_active' => true,
            ],
            [
                'account_number' => '7777888899',
                'account_name' => 'Petty Cash Account',
                'bank_name' => 'TD Bank',
                'branch_name' => 'Local Branch',
                'account_type' => '0',
                'payment_gateway' => null,
                'opening_balance' => 10000.00,
                'current_balance' => 85000.00,
                'iban' => 'US44TDBUS33',
                'swift_code' => 'TDBKUS33',
                'routing_number' => '031201360',
                'is_active' => false,
            ],
        ];

        $bankGLAccounts = ChartOfAccount::where('created_by', $userId)
            ->whereBetween('account_code', ['1000', '1099'])
            ->pluck('id')
            ->toArray();

        foreach ($bankAccounts as $index => $account) {
            BankAccount::create(array_merge($account, [
                'gl_account_id' => $bankGLAccounts[$index % count($bankGLAccounts)] ?? null,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
