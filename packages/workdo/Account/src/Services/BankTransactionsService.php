<?php

namespace Workdo\Account\Services;

use Carbon\Carbon;
use Workdo\Account\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\VendorPayment;

class BankTransactionsService
{
    private function buildPaymentMethodLabel(?string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'card' => 'Card',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other',
            default => 'Bank Transfer',
        };
    }

    private function buildMobileProviderLabel(?string $provider): ?string
    {
        return match ($provider) {
            'mpesa' => 'M-Pesa',
            'emola' => 'e-Mola',
            'mkesh' => 'mKesh',
            default => null,
        };
    }

    private function buildPaymentDetails(?string $paymentMethod, ?string $mobileProvider): string
    {
        $parts = [$this->buildPaymentMethodLabel($paymentMethod)];
        $providerLabel = $this->buildMobileProviderLabel($mobileProvider);

        if ($providerLabel) {
            $parts[] = $providerLabel;
        }

        return implode(' / ', array_filter($parts));
    }

    public function createVendorPayment($vendorPayment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $vendorPayment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $vendorPayment->payment_amount : -$vendorPayment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $vendorPayment->bank_account_id;
        $bankTransaction->transaction_date = $vendorPayment->payment_date;
        $bankTransaction->transaction_type = 'debit';
        $bankTransaction->reference_number = $vendorPayment->payment_number;
        $bankTransaction->description = 'Vendor Payment #' . $vendorPayment->payment_number
            . ' - ' . $vendorPayment->vendor->name
            . ' (' . $this->buildPaymentDetails($vendorPayment->payment_method, $vendorPayment->mobile_money_provider) . ')';
        $bankTransaction->amount = $vendorPayment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

         // Update bank account balance
        $this->updateBankBalance($vendorPayment->bank_account_id, -$vendorPayment->payment_amount);
    }

    public function createCustomerPayment($customerPayment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $customerPayment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $customerPayment->payment_amount : $customerPayment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $customerPayment->bank_account_id;
        $bankTransaction->transaction_date = $customerPayment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $customerPayment->payment_number;
        $bankTransaction->description = 'Customer Payment #' . $customerPayment->payment_number
            . ' - ' . $customerPayment->customer->name
            . ' (' . $this->buildPaymentDetails($customerPayment->payment_method, $customerPayment->mobile_money_provider) . ')';
        $bankTransaction->amount = $customerPayment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($customerPayment->bank_account_id, $customerPayment->payment_amount);
    }

    public function createTransferBankTransactions($transfer)
    {
        // Get running balance for source account
        $fromLastTransaction = BankTransaction::where('bank_account_id', $transfer->from_account_id)->orderBy('id', 'desc')->first();
        $fromRunningBalance = $fromLastTransaction ? $fromLastTransaction->running_balance - $transfer->transfer_amount : -$transfer->transfer_amount;

        // Debit transaction from source account
        $debitTransaction = new BankTransaction();
        $debitTransaction->bank_account_id = $transfer->from_account_id;
        $debitTransaction->transaction_date = $transfer->transfer_date;
        $debitTransaction->transaction_type = 'debit';
        $debitTransaction->reference_number = $transfer->transfer_number;
        $debitTransaction->description = 'Transfer to ' . $transfer->toAccount->account_name;
        $debitTransaction->amount = $transfer->transfer_amount;
        $debitTransaction->running_balance = $fromRunningBalance;
        $debitTransaction->transaction_status = 'cleared';
        $debitTransaction->reconciliation_status = 'unreconciled';
        $debitTransaction->created_by = creatorId();
        $debitTransaction->save();

        // Get running balance for destination account
        $toLastTransaction = BankTransaction::where('bank_account_id', $transfer->to_account_id)->orderBy('id', 'desc')->first();
        $toRunningBalance = $toLastTransaction ? $toLastTransaction->running_balance + $transfer->transfer_amount : $transfer->transfer_amount;

        // Credit transaction to destination account
        $creditTransaction = new BankTransaction();
        $creditTransaction->bank_account_id = $transfer->to_account_id;
        $creditTransaction->transaction_date = $transfer->transfer_date;
        $creditTransaction->transaction_type = 'credit';
        $creditTransaction->reference_number = $transfer->transfer_number;
        $creditTransaction->description = 'Transfer from ' . $transfer->fromAccount->account_name;
        $creditTransaction->amount = $transfer->transfer_amount;
        $creditTransaction->running_balance = $toRunningBalance;
        $creditTransaction->transaction_status = 'cleared';
        $creditTransaction->reconciliation_status = 'unreconciled';
        $creditTransaction->created_by = creatorId();
        $creditTransaction->save();

        // Additional debit for transfer charges (if any)
        if ($transfer->transfer_charges > 0) {
            $chargesRunningBalance = $fromRunningBalance - $transfer->transfer_charges;

            $chargesTransaction = new BankTransaction();
            $chargesTransaction->bank_account_id = $transfer->from_account_id;
            $chargesTransaction->transaction_date = $transfer->transfer_date;
            $chargesTransaction->transaction_type = 'debit';
            $chargesTransaction->reference_number = $transfer->transfer_number . '-CHARGES';
            $chargesTransaction->description = 'Transfer charges for ' . $transfer->transfer_number;
            $chargesTransaction->amount = $transfer->transfer_charges;
            $chargesTransaction->running_balance = $chargesRunningBalance;
            $chargesTransaction->transaction_status = 'cleared';
            $chargesTransaction->reconciliation_status = 'unreconciled';
            $chargesTransaction->created_by = creatorId();
            $chargesTransaction->save();
        }
    }

    public function updateBankBalance($bankAccountId, $amount) {
        $bankAccount = BankAccount::find($bankAccountId);
        $bankAccount->current_balance += $amount;
        $bankAccount->save();

        // Update running balance for latest transaction
        $latestTransaction = BankTransaction::where('bank_account_id', $bankAccountId)
                            ->latest()
                            ->first();

        if ($latestTransaction) {
            $latestTransaction->running_balance = $bankAccount->current_balance;
            $latestTransaction->save();
        }
    }
    public function createRetainerPayment($retainerPayment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $retainerPayment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $retainerPayment->payment_amount : $retainerPayment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $retainerPayment->bank_account_id;
        $bankTransaction->transaction_date = $retainerPayment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $retainerPayment->payment_number;
        $bankTransaction->description = 'Retainer Payment #' . $retainerPayment->payment_number . ' - ' . $retainerPayment->customer->name;
        $bankTransaction->amount = $retainerPayment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($retainerPayment->bank_account_id, $retainerPayment->payment_amount);
    }

    public function createRevenuePayment($revenue)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $revenue->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $revenue->amount : $revenue->amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $revenue->bank_account_id;
        $bankTransaction->transaction_date = $revenue->revenue_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $revenue->revenue_number;
        $bankTransaction->description = 'Revenue Posted: ' . ($revenue->description ?? 'Revenue transaction');
        $bankTransaction->amount = $revenue->amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($revenue->bank_account_id, $revenue->amount);
    }

    public function createExpensePayment($expense)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $expense->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $expense->amount : -$expense->amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $expense->bank_account_id;
        $bankTransaction->transaction_date = $expense->expense_date;
        $bankTransaction->transaction_type = 'debit';
        $bankTransaction->reference_number = $expense->expense_number;
        $bankTransaction->description = 'Expense Posted: ' . ($expense->description ?? 'Expense transaction');
        $bankTransaction->amount = $expense->amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance (negative amount to decrease balance)
        $this->updateBankBalance($expense->bank_account_id, -$expense->amount);
    }
    public function createCommissionPayment($commissionPayment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $commissionPayment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $commissionPayment->payment_amount : -$commissionPayment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $commissionPayment->bank_account_id;
        $bankTransaction->transaction_date = $commissionPayment->payment_date;
        $bankTransaction->transaction_type = 'debit';
        $bankTransaction->reference_number = $commissionPayment->payment_number;
        $bankTransaction->description = 'Commission Payment #' . $commissionPayment->payment_number . ' - ' . $commissionPayment->agent->name;
        $bankTransaction->amount = $commissionPayment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance (negative amount to decrease balance)
        $this->updateBankBalance($commissionPayment->bank_account_id, -$commissionPayment->payment_amount);
    }

    public function createPayrollPayment($payrollEntry)
    {
        $bankAccountId = $payrollEntry->payroll->bank_account_id;
        $lastTransaction = BankTransaction::where('bank_account_id', $bankAccountId)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $payrollEntry->net_pay : -$payrollEntry->net_pay;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $bankAccountId;
        $bankTransaction->transaction_date = now();
        $bankTransaction->transaction_type = 'debit';
        $bankTransaction->reference_number = 'PAYROLL-' . $payrollEntry->id;
        $bankTransaction->description = 'Salary Payment - ' . $payrollEntry->employee->user->name;
        $bankTransaction->amount = $payrollEntry->net_pay;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        $this->updateBankBalance($bankAccountId, -$payrollEntry->net_pay);
    }

    public function createPosPayment($posSale, $bankAccountId)
    {
        $posSale->load('payment');
        $amount = $posSale->payment->discount_amount ?? 0;

        $lastTransaction = BankTransaction::where('bank_account_id', $bankAccountId)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $amount : $amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $bankAccountId;
        $bankTransaction->transaction_date = $posSale->pos_date ?? now();
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $posSale->sale_number;
        $bankTransaction->description = 'POS Sale ' . $posSale->sale_number;
        $bankTransaction->amount = $amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        $this->updateBankBalance($bankAccountId, $amount);
    }

    public function createMobileServicePayment($payment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $payment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
         $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $payment->payment_amount : $payment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $payment->bank_account_id;
        $bankTransaction->transaction_date = $payment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $payment->payment_number;
        $bankTransaction->description = 'Mobile Service Payment: ' . ($payment->description ?? 'Mobile service transaction');
        $bankTransaction->amount = $payment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($payment->bank_account_id, $payment->payment_amount);
    }

    public function createMarkFleetBookingPayment($payment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $payment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $payment->payment_amount : $payment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $payment->bank_account_id;
        $bankTransaction->transaction_date = $payment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $payment->payment_number;
        $bankTransaction->description = 'Fleet Booking Payment: ' . ($payment->description ?? 'Fleet booking transaction');
        $bankTransaction->amount = $payment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($payment->bank_account_id, $payment->payment_amount);
    }

    public function createBeautyBookingPayment($booking)
    {
        // Find bank account by payment gateway
        $bankAccount = BankAccount::where('payment_gateway', $booking->payment_option)->where('created_by', $booking->created_by)
            ->first();
        if (!$bankAccount) {
            throw new \Exception('Bank account not found for payment gateway: ' . $booking->payment_option);
        }

        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $bankAccount->id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $booking->price : $booking->price;
        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $bankAccount->id;
        $bankTransaction->transaction_date = $booking->date ?? now();
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $booking->payment_number ?? 'BEAUTY-' . $booking->id;
        $bankTransaction->description = 'Beauty Booking Payment via ' . $booking->payment_option;
        $bankTransaction->amount = $booking->price;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = $booking->created_by;
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($bankAccount->id, $booking->price);
    }

    public function createDairyCattlePayment($dairyCattlePayment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $dairyCattlePayment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $dairyCattlePayment->payment_amount : $dairyCattlePayment->payment_amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $dairyCattlePayment->bank_account_id;
        $bankTransaction->transaction_date = $dairyCattlePayment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $dairyCattlePayment->payment_number;
        $bankTransaction->description = 'Dairy Cattle Payment: ' . ($dairyCattlePayment->description ?? 'Dairy cattle transaction');
        $bankTransaction->amount = $dairyCattlePayment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($dairyCattlePayment->bank_account_id, $dairyCattlePayment->payment_amount);
    }

    public function createCateringOrderPayment($payment)
    {
        // Get current running balance for the bank account
        $lastTransaction = BankTransaction::where('bank_account_id', $payment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $payment->amount : $payment->amount;

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $payment->bank_account_id;
        $bankTransaction->transaction_date = $payment->payment_date;
        $bankTransaction->transaction_type = 'credit';
        $bankTransaction->reference_number = $payment->reference_number;
        $bankTransaction->description = 'Catering Order Payment #' . $payment->id;
        $bankTransaction->amount = $payment->amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        // Update bank account balance
        $this->updateBankBalance($payment->bank_account_id, $payment->amount);
    }

    public function createUpdateSalesAgentCommissionPayment($payment)
    {
        $lastTransaction = BankTransaction::where('bank_account_id', $payment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $payment->payment_amount : -$payment->payment_amount;

        $agentName = $payment->agent && $payment->agent->user ? $payment->agent->user->name : 'Agent';

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $payment->bank_account_id;
        $bankTransaction->transaction_date = $payment->payment_date;
        $bankTransaction->transaction_type = 'debit';
        $bankTransaction->reference_number = $payment->payment_number;
        $bankTransaction->description = 'Commission Payment #' . $payment->payment_number . ' - ' . $agentName;
        $bankTransaction->amount = $payment->payment_amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        $this->updateBankBalance($payment->bank_account_id, -$payment->payment_amount);
    }

    public function createCommissionAdjustmentBankTransaction($adjustment)
    {
        // Only create bank transaction if adjustment has bank_account_id
        if (!isset($adjustment->bank_account_id) || !$adjustment->bank_account_id) {
            return;
        }
        $lastTransaction = BankTransaction::where('bank_account_id', $adjustment->bank_account_id)
            ->orderBy('id', 'desc')
            ->first();
        $agentName = $adjustment->agent && $adjustment->agent->user ? $adjustment->agent->user->name : 'Agent';
        $amount = abs($adjustment->adjustment_amount);

        // Bonus/Correction(+) = Debit (cash out to agent)
        // Penalty/Correction(-) = Credit (cash in from agent)
        if ($adjustment->adjustment_type === 'bonus' || ($adjustment->adjustment_type === 'correction' && $adjustment->adjustment_amount > 0)) {
            $transactionType = 'debit';
            $runningBalance = $lastTransaction ? $lastTransaction->running_balance - $amount : -$amount;
            $balanceChange = -$amount;
        } else {
            $transactionType = 'credit';
            $runningBalance = $lastTransaction ? $lastTransaction->running_balance + $amount : $amount;
            $balanceChange = $amount;
        }

        $bankTransaction = new BankTransaction();
        $bankTransaction->bank_account_id = $adjustment->bank_account_id;
        $bankTransaction->transaction_date = $adjustment->adjustment_date;
        $bankTransaction->transaction_type = $transactionType;
        $bankTransaction->reference_number = 'ADJ-' . $adjustment->id;
        $bankTransaction->description = 'Commission Adjustment (' . ucfirst($adjustment->adjustment_type) . ') - ' . $agentName;
        $bankTransaction->amount = $amount;
        $bankTransaction->running_balance = $runningBalance;
        $bankTransaction->transaction_status = 'cleared';
        $bankTransaction->reconciliation_status = 'unreconciled';
        $bankTransaction->created_by = creatorId();
        $bankTransaction->save();

        $this->updateBankBalance($adjustment->bank_account_id, $balanceChange);
    }

    public function importBankStatementCsv(string $filePath, int $bankAccountId, int $companyId): array
    {
        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', $companyId)
            ->first();

        if (!$bankAccount) {
            throw new \InvalidArgumentException('Invalid bank account selected.');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Unable to read CSV file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow || count($headerRow) === 0) {
            fclose($handle);
            throw new \InvalidArgumentException('CSV header not found.');
        }

        $headerMap = $this->buildCsvHeaderMap($headerRow);
        $required = ['transaction_date', 'amount'];
        foreach ($required as $requiredColumn) {
            if (!array_key_exists($requiredColumn, $headerMap)) {
                fclose($handle);
                throw new \InvalidArgumentException("Required CSV column not found: {$requiredColumn}");
            }
        }

        $created = 0;
        $duplicates = 0;
        $errors = 0;
        $line = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($this->isCsvRowEmpty($row)) {
                    continue;
                }

                try {
                    $transactionDateRaw = $this->csvValue($row, $headerMap, 'transaction_date');
                    $amountRaw = $this->csvValue($row, $headerMap, 'amount');
                    $transactionTypeRaw = $this->csvValue($row, $headerMap, 'transaction_type');
                    $referenceRaw = $this->csvValue($row, $headerMap, 'reference_number');
                    $descriptionRaw = $this->csvValue($row, $headerMap, 'description');
                    $runningBalanceRaw = $this->csvValue($row, $headerMap, 'running_balance');
                    $statusRaw = $this->csvValue($row, $headerMap, 'transaction_status');

                    $transactionDate = $this->parseCsvDate($transactionDateRaw);
                    $amountSigned = $this->parseCsvAmount($amountRaw);
                    $transactionType = $this->resolveTransactionType($transactionTypeRaw, $amountSigned);
                    $amount = abs($amountSigned);
                    $reference = $referenceRaw ?: null;
                    $description = $descriptionRaw ?: '-';
                    $transactionStatus = in_array(strtolower((string) $statusRaw), ['pending', 'cleared', 'cancelled'], true)
                        ? strtolower((string) $statusRaw)
                        : 'cleared';

                    $lastTransaction = BankTransaction::query()
                        ->where('bank_account_id', $bankAccountId)
                        ->where('created_by', $companyId)
                        ->latest('id')
                        ->first();

                    $baseBalance = $lastTransaction
                        ? (float) $lastTransaction->running_balance
                        : (float) $bankAccount->current_balance;

                    $computedBalance = $transactionType === 'credit'
                        ? $baseBalance + $amount
                        : $baseBalance - $amount;

                    $runningBalance = $runningBalanceRaw !== null && $runningBalanceRaw !== ''
                        ? $this->parseCsvAmount($runningBalanceRaw)
                        : $computedBalance;

                    $exists = BankTransaction::query()
                        ->where('bank_account_id', $bankAccountId)
                        ->where('created_by', $companyId)
                        ->whereDate('transaction_date', $transactionDate->toDateString())
                        ->where('transaction_type', $transactionType)
                        ->where('amount', number_format($amount, 2, '.', ''))
                        ->where('reference_number', $reference)
                        ->where('description', $description)
                        ->exists();

                    if ($exists) {
                        $duplicates++;
                        continue;
                    }

                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'transaction_date' => $transactionDate->toDateString(),
                        'transaction_type' => $transactionType,
                        'reference_number' => $reference,
                        'description' => $description,
                        'amount' => $amount,
                        'running_balance' => $runningBalance,
                        'transaction_status' => $transactionStatus,
                        'reconciliation_status' => 'unreconciled',
                        'created_by' => $companyId,
                    ]);

                    $created++;
                } catch (\Throwable) {
                    $errors++;
                }
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            fclose($handle);
            throw $exception;
        }

        fclose($handle);

        return [
            'created' => $created,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'processed_rows' => $created + $duplicates + $errors,
        ];
    }

    public function autoReconcileImportedTransactions(
        int $companyId,
        ?int $bankAccountId = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        float $tolerance = 0.01,
        int $dayWindow = 3
    ): array {
        $query = BankTransaction::query()
            ->where('created_by', $companyId)
            ->where('reconciliation_status', 'unreconciled');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $transactions = $query->orderBy('transaction_date')->orderBy('id')->get();

        $reconciled = 0;
        $unmatched = 0;

        foreach ($transactions as $transaction) {
            $matched = $this->matchTransactionToPayment(
                $transaction,
                $companyId,
                $tolerance,
                $dayWindow
            );

            if ($matched) {
                $transaction->reconciliation_status = 'reconciled';
                $transaction->save();
                $reconciled++;
            } else {
                $unmatched++;
            }
        }

        return [
            'processed' => $transactions->count(),
            'reconciled' => $reconciled,
            'unmatched' => $unmatched,
        ];
    }

    private function matchTransactionToPayment(
        BankTransaction $transaction,
        int $companyId,
        float $tolerance,
        int $dayWindow
    ): bool {
        $preferredSource = $transaction->transaction_type === 'credit' ? 'customer' : 'vendor';

        $matchers = $preferredSource === 'customer'
            ? ['customer', 'vendor']
            : ['vendor', 'customer'];

        foreach ($matchers as $matcher) {
            $candidate = $matcher === 'customer'
                ? $this->findCustomerPaymentCandidate($transaction, $companyId, $tolerance, $dayWindow)
                : $this->findVendorPaymentCandidate($transaction, $companyId, $tolerance, $dayWindow);

            if ($candidate) {
                return true;
            }
        }

        return false;
    }

    private function findCustomerPaymentCandidate(
        BankTransaction $transaction,
        int $companyId,
        float $tolerance,
        int $dayWindow
    ): ?CustomerPayment {
        $dateFrom = Carbon::parse($transaction->transaction_date)->subDays($dayWindow)->toDateString();
        $dateTo = Carbon::parse($transaction->transaction_date)->addDays($dayWindow)->toDateString();

        $query = CustomerPayment::query()
            ->where('created_by', $companyId)
            ->where('bank_account_id', $transaction->bank_account_id)
            ->where('status', 'cleared')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->whereRaw('ABS(payment_amount - ?) <= ?', [(float) $transaction->amount, $tolerance]);

        $normalizedReference = $this->normalizeReference($transaction->reference_number);

        if ($normalizedReference !== '') {
            $query->where(function ($inner) use ($normalizedReference) {
                $inner->whereRaw('REPLACE(REPLACE(UPPER(payment_number), " ", ""), "-", "") = ?', [$normalizedReference])
                    ->orWhereRaw('REPLACE(REPLACE(UPPER(COALESCE(reference_number, "")), " ", ""), "-", "") = ?', [$normalizedReference]);
            });
        }

        return $query->orderByDesc('payment_date')->first();
    }

    private function findVendorPaymentCandidate(
        BankTransaction $transaction,
        int $companyId,
        float $tolerance,
        int $dayWindow
    ): ?VendorPayment {
        $dateFrom = Carbon::parse($transaction->transaction_date)->subDays($dayWindow)->toDateString();
        $dateTo = Carbon::parse($transaction->transaction_date)->addDays($dayWindow)->toDateString();

        $query = VendorPayment::query()
            ->where('created_by', $companyId)
            ->where('bank_account_id', $transaction->bank_account_id)
            ->where('status', 'cleared')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->whereRaw('ABS(payment_amount - ?) <= ?', [(float) $transaction->amount, $tolerance]);

        $normalizedReference = $this->normalizeReference($transaction->reference_number);

        if ($normalizedReference !== '') {
            $query->where(function ($inner) use ($normalizedReference) {
                $inner->whereRaw('REPLACE(REPLACE(UPPER(payment_number), " ", ""), "-", "") = ?', [$normalizedReference])
                    ->orWhereRaw('REPLACE(REPLACE(UPPER(COALESCE(reference_number, "")), " ", ""), "-", "") = ?', [$normalizedReference]);
            });
        }

        return $query->orderByDesc('payment_date')->first();
    }

    private function buildCsvHeaderMap(array $header): array
    {
        $map = [];
        foreach ($header as $index => $column) {
            $normalized = $this->normalizeCsvHeader($column);
            if ($normalized !== '') {
                $map[$normalized] = $index;
            }
        }

        $aliases = [
            'date' => 'transaction_date',
            'data' => 'transaction_date',
            'data_transacao' => 'transaction_date',
            'transactiondate' => 'transaction_date',
            'type' => 'transaction_type',
            'tipo' => 'transaction_type',
            'reference' => 'reference_number',
            'referencia' => 'reference_number',
            'ref' => 'reference_number',
            'details' => 'description',
            'descricao' => 'description',
            'valor' => 'amount',
            'montante' => 'amount',
            'balance' => 'running_balance',
            'saldo' => 'running_balance',
            'status' => 'transaction_status',
        ];

        foreach ($aliases as $alias => $target) {
            if (!isset($map[$target]) && isset($map[$alias])) {
                $map[$target] = $map[$alias];
            }
        }

        return $map;
    }

    private function normalizeCsvHeader(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace([' ', '-', '.'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
        return $value;
    }

    private function csvValue(array $row, array $headerMap, string $column): ?string
    {
        if (!array_key_exists($column, $headerMap)) {
            return null;
        }

        $value = $row[$headerMap[$column]] ?? null;
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function parseCsvDate(?string $value): Carbon
    {
        if (!$value) {
            throw new \InvalidArgumentException('Invalid date');
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // continue
            }
        }

        return Carbon::parse($value)->startOfDay();
    }

    private function parseCsvAmount(?string $value): float
    {
        if ($value === null) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        $normalized = trim($value);
        $normalized = str_replace(' ', '', $normalized);

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        return (float) $normalized;
    }

    private function resolveTransactionType(?string $type, float $signedAmount): string
    {
        $normalizedType = strtolower(trim((string) $type));

        if (in_array($normalizedType, ['debit', 'debito', 'd', 'saida', 'out'], true)) {
            return 'debit';
        }

        if (in_array($normalizedType, ['credit', 'credito', 'c', 'entrada', 'in'], true)) {
            return 'credit';
        }

        return $signedAmount < 0 ? 'debit' : 'credit';
    }

    private function normalizeReference(?string $reference): string
    {
        $value = strtoupper(trim((string) $reference));
        return str_replace([' ', '-'], '', $value);
    }

    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
