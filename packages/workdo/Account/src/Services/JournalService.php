<?php

namespace Workdo\Account\Services;

use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Events\UpdateBudgetSpending;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Retainer\Models\RetainerPaymentAllocation;

/**
 * JournalService - Automatic double-entry journal creation for all transactions
 * Usage: Call from transaction controllers after creating records (Invoice, Payment, etc.)
 */
class JournalService
{
    private ?array $mozambiqueTaxAccountCodes = null;

    private function validateAccounts(array $accountCodes , $userID = null)
    {
        foreach ($accountCodes as $code) {
            $account = ChartOfAccount::where('account_code', $code)
                ->where('created_by', $userID ?? creatorId())
                ->first();
            if (!$account) {
                throw new \Exception("Account with code {$code} not found");
            }
        }
    }

    private function validateBalance($totalDebit, $totalCredit)
    {
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Journal entry not balanced: Debit {$totalDebit} != Credit {$totalCredit}");
        }
    }

    private function getVatOutputTaxAccountCode(): string
    {
        return $this->resolveMozambiqueTaxAccountCodes()['vat_output'];
    }

    private function getVatInputTaxAccountCode(): string
    {
        return $this->resolveMozambiqueTaxAccountCodes()['vat_input'];
    }

    private function resolveMozambiqueTaxAccountCodes(): array
    {
        if ($this->mozambiqueTaxAccountCodes !== null) {
            return $this->mozambiqueTaxAccountCodes;
        }

        $codes = [
            'vat_output' => '2210',
            'vat_input' => '1500',
        ];

        if (!Schema::hasTable('mz_tax_account_mappings')) {
            $this->mozambiqueTaxAccountCodes = $codes;
            return $codes;
        }

        $today = now()->toDateString();

        try {
            $mapping = MozTaxAccountMapping::query()
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->with(['vatOutputAccount:id,account_code', 'vatInputAccount:id,account_code'])
                ->latest('effective_from')
                ->latest('id')
                ->first();
        } catch (\Throwable) {
            $this->mozambiqueTaxAccountCodes = $codes;
            return $codes;
        }

        if ($mapping?->vatOutputAccount?->account_code) {
            $codes['vat_output'] = $mapping->vatOutputAccount->account_code;
        }

        if ($mapping?->vatInputAccount?->account_code) {
            $codes['vat_input'] = $mapping->vatInputAccount->account_code;
        }

        $this->mozambiqueTaxAccountCodes = $codes;

        return $codes;
    }

    /**
     * Creates journal entry for sales invoice: Dr: A/R, Cr: Sales Revenue + Tax
     * Usage: SalesInvoiceController->store() after creating invoice
     */
    public function createSalesInvoiceJournal($salesInvoice)
    {
        $vatOutputTaxCode = $this->getVatOutputTaxAccountCode();

        // Validate required accounts exist
        $requiredAccounts = ['1100', '4100'];
        if ($salesInvoice->tax_amount > 0) {
            $requiredAccounts[] = $vatOutputTaxCode;
        }
        $this->validateAccounts($requiredAccounts);
        // Validate amounts balance
        $totalDebit = $salesInvoice->total_amount;
        $totalCredit = $salesInvoice->subtotal - $salesInvoice->discount_amount + ($salesInvoice->tax_amount ?? 0);
        $this->validateBalance($totalDebit, $totalCredit);

        $arAccount = ChartOfAccount::where('account_code', '1100')->where('created_by', creatorId())->first();
        $salesAccount = ChartOfAccount::where('account_code', '4100')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatOutputTaxCode)->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $salesInvoice->invoice_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'sales_invoice',
            'reference_id' => $salesInvoice->id,
            'description' => 'Sales Invoice #' . $salesInvoice->invoice_number,
            'total_debit' => $salesInvoice->total_amount,
            'total_credit' => $salesInvoice->total_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Accounts Receivable
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'description' => 'Sales to ' . $salesInvoice->customer->name,
            'debit_amount' => $salesInvoice->total_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Sales Revenue
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $salesAccount->id,
            'description' => 'Product sales',
            'debit_amount' => 0,
            'credit_amount' => $salesInvoice->subtotal - $salesInvoice->discount_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Tax Payable (if tax exists)
        if ($salesInvoice->tax_amount > 0) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Sales tax collected',
                'debit_amount' => 0,
                'credit_amount' => $salesInvoice->tax_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    public function createSalesRetainerToInvoiceJournal($retainer)
    {

        $allocations = RetainerPaymentAllocation::whereHas('payment', function($q) {
                $q->where('status', 'cleared');
            })
            ->where('retainer_id', $retainer->id)
            ->get();

        $totalAmount = $allocations->sum('allocated_amount');

        if ($totalAmount <= 0) {
            return null;
        }

        $this->validateAccounts(['2350', '1100']);
        $this->validateBalance($totalAmount, $totalAmount);

        $customerDepositsAccount = ChartOfAccount::where('account_code', '2350')->where('created_by', creatorId())->first();
        $arAccount = ChartOfAccount::where('account_code', '1100')->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => now(),
            'entry_type' => 'automatic',
            'reference_type' => 'retainer_to_invoice',
            'reference_id' => $retainer->id,
            'description' => 'Retainer converted to invoice',
            'total_debit' => $totalAmount,
            'total_credit' => $totalAmount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $customerDepositsAccount->id,
            'description' => 'Retainer converted to invoice',
            'debit_amount' => $totalAmount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'description' => 'Retainer converted to invoice',
            'debit_amount' => 0,
            'credit_amount' => $totalAmount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }
    /**
     * Creates journal entry for service invoice: Dr: A/R, Cr: Service Revenue + Tax
     * Usage: PostSalesInvoiceListener for service type invoices
     */
    public function createServiceInvoiceJournal($salesInvoice)
    {
        $vatOutputTaxCode = $this->getVatOutputTaxAccountCode();

        $requiredAccounts = ['1100', '4200'];
        if ($salesInvoice->tax_amount > 0) {
            $requiredAccounts[] = $vatOutputTaxCode;
        }
        $this->validateAccounts($requiredAccounts);

        $totalDebit = $salesInvoice->total_amount;
        $totalCredit = $salesInvoice->subtotal - $salesInvoice->discount_amount + ($salesInvoice->tax_amount ?? 0);
        $this->validateBalance($totalDebit, $totalCredit);

        $arAccount = ChartOfAccount::where('account_code', '1100')->where('created_by', creatorId())->first();
        $serviceRevenueAccount = ChartOfAccount::where('account_code', '4200')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatOutputTaxCode)->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $salesInvoice->invoice_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'service_invoice',
            'reference_id' => $salesInvoice->id,
            'description' => 'Service Invoice #' . $salesInvoice->invoice_number,
            'total_debit' => $salesInvoice->total_amount,
            'total_credit' => $salesInvoice->total_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'description' => 'Service to ' . $salesInvoice->customer->name,
            'debit_amount' => $salesInvoice->total_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $serviceRevenueAccount->id,
            'description' => 'Service revenue',
            'debit_amount' => 0,
            'credit_amount' => $salesInvoice->subtotal - $salesInvoice->discount_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        if ($salesInvoice->tax_amount > 0) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Sales tax collected',
                'debit_amount' => 0,
                'credit_amount' => $salesInvoice->tax_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for purchase inventory: Dr: Inventory + Tax, Cr: A/P
     * Usage: PostPurchaseInvoiceListener after creating invoice
     */
    public function createPurchaseInventoryJournal($purchaseInvoice)
    {
        $vatInputTaxCode = $this->getVatInputTaxAccountCode();

        $requiredAccounts = ['2000', '1200'];
        if ($purchaseInvoice->tax_amount > 0) {
            $requiredAccounts[] = $vatInputTaxCode;
        }

        $this->validateAccounts($requiredAccounts);

        $totalDebit = $purchaseInvoice->subtotal - $purchaseInvoice->discount_amount + ($purchaseInvoice->tax_amount ?? 0);
        $totalCredit = $purchaseInvoice->total_amount;
        $this->validateBalance($totalDebit, $totalCredit);

        $apAccount = ChartOfAccount::where('account_code', '2000')->where('created_by', creatorId())->first();
        $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatInputTaxCode)->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $purchaseInvoice->invoice_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'purchase_invoice',
            'reference_id' => $purchaseInvoice->id,
            'description' => 'Purchase Invoice #' . $purchaseInvoice->invoice_number,
            'total_debit' => $purchaseInvoice->total_amount,
            'total_credit' => $purchaseInvoice->total_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'Purchase from ' . $purchaseInvoice->vendor->name,
            'debit_amount' => $purchaseInvoice->subtotal - $purchaseInvoice->discount_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        if ($purchaseInvoice->tax_amount > 0) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Purchase tax paid',
                'debit_amount' => $purchaseInvoice->tax_amount,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $apAccount->id,
            'description' => 'Purchase from vendor',
            'debit_amount' => 0,
            'credit_amount' => $purchaseInvoice->total_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for customer payment: Dr: Bank, Cr: A/R
     * Usage: CustomerPaymentController->updateStatus() when payment is cleared
     */
    public function createCustomerPaymentJournal($customerPayment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = $customerPayment->bankAccount->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate A/R account exists
        $this->validateAccounts(['1100']);
        $arAccount = ChartOfAccount::where('account_code', '1100')->where('created_by', creatorId())->first();

        // Validate amounts balance
        $this->validateBalance($customerPayment->payment_amount, $customerPayment->payment_amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $customerPayment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'customer_payment',
            'reference_id' => $customerPayment->id,
            'description' => 'Customer Payment #' . $customerPayment->payment_number,
            'total_debit' => $customerPayment->payment_amount,
            'total_credit' => $customerPayment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Payment received from ' . $customerPayment->customer->name,
            'debit_amount' => $customerPayment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Accounts Receivable
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'description' => 'Payment from customer',
            'debit_amount' => 0,
            'credit_amount' => $customerPayment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for vendor payment: Dr: A/P, Cr: Bank
     * Usage: VendorPaymentController->store() after making payment
     */
    public function createVendorPaymentJournal($vendorPayment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = $vendorPayment->bankAccount->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate A/P account exists
        $this->validateAccounts(['2000']);
        $apAccount = ChartOfAccount::where('account_code', '2000')->where('created_by', creatorId())->first();
        // Validate amounts balance
        $this->validateBalance($vendorPayment->payment_amount, $vendorPayment->payment_amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $vendorPayment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'vendor_payment',
            'reference_id' => $vendorPayment->id,
            'description' => 'Vendor Payment #' . $vendorPayment->payment_number,
            'total_debit' => $vendorPayment->payment_amount,
            'total_credit' => $vendorPayment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);
        // Debit: Accounts Payable
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $apAccount->id,
            'description' => 'Payment to ' . $vendorPayment->vendor->name,
            'debit_amount' => $vendorPayment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Payment from ' . $vendorPayment->bankAccount->account_name,
            'debit_amount' => 0,
            'credit_amount' => $vendorPayment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for revenue: Dr: Bank, Cr: Revenue
     * Usage: RevenueController->store() after recording revenue
     */
    public function createRevenueEntryJournal($revenueEntry)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = $revenueEntry->bankAccount->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Use selected chart of account
        $revenueAccount = $revenueEntry->chartOfAccount;
        if (!$revenueAccount) {
            throw new \Exception("Revenue account not found");
        }

        // Validate amounts balance
        $this->validateBalance($revenueEntry->amount, $revenueEntry->amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $revenueEntry->entry_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'revenue',
            'reference_id' => $revenueEntry->id,
            'description' => 'Revenue Entry - #' . $revenueEntry->revenue_number,
            'total_debit' => $revenueEntry->amount,
            'total_credit' => $revenueEntry->amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Revenue received',
            'debit_amount' => $revenueEntry->amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Selected Revenue Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'description' => 'Revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $revenueEntry->amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for expense: Dr: Expense, Cr: Bank
     * Usage: ExpenseController->store() after recording expense
     */
    public function createExpenseEntryJournal($expenseEntry)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = $expenseEntry->bankAccount->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Use selected chart of account
        $expenseAccount = $expenseEntry->chartOfAccount;
        if (!$expenseAccount) {
            throw new \Exception("Expense account not found");
        }

        // Validate amounts balance
        $this->validateBalance($expenseEntry->amount, $expenseEntry->amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $expenseEntry->entry_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'expense',
            'reference_id' => $expenseEntry->id,
            'description' => 'Expense Entry - #' . $expenseEntry->expense_number,
            'total_debit' => $expenseEntry->amount,
            'total_credit' => $expenseEntry->amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Selected Expense Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $expenseAccount->id,
            'description' => 'Expense incurred',
            'debit_amount' => $expenseEntry->amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Payment made',
            'debit_amount' => 0,
            'credit_amount' => $expenseEntry->amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for stock transfer: Dr: To Warehouse, Cr: From Warehouse
     * Usage: StockTransferController->store() after transferring inventory
     */
    public function createStockTransferJournal($stockTransfer)
    {
        // Calculate transfer value (quantity * product cost or use default value)
        $transferValue = $stockTransfer->quantity * ($stockTransfer->product->purchase_price ?? 1);

        // Validate required accounts exist
        $this->validateAccounts(['1200']); // Inventory account

        // Validate amounts balance
        $this->validateBalance($transferValue, $transferValue);

        $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $stockTransfer->date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'stock_transfer',
            'reference_id' => $stockTransfer->id,
            'description' => 'Stock Transfer #' . $stockTransfer->id . ' - ' . $stockTransfer->fromWarehouse->name . ' to ' . $stockTransfer->toWarehouse->name,
            'total_debit' => $transferValue,
            'total_credit' => $transferValue,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: To Warehouse Inventory
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'Stock received at ' . $stockTransfer->toWarehouse->name,
            'debit_amount' => $transferValue,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: From Warehouse Inventory
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'Stock transferred from ' . $stockTransfer->fromWarehouse->name,
            'debit_amount' => 0,
            'credit_amount' => $transferValue,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }


    /**
     * Deletes journal entry and items for stock transfer
     * Usage: DeleteTransferListener after transfer deletion
     */
    public function deleteStockTransferJournal($transferId)
    {
        $journalEntry = JournalEntry::where('reference_type', 'stock_transfer')
                                  ->where('reference_id', $transferId)
                                  ->first();

        if ($journalEntry) {
            // Reverse account balances before deleting
            $this->reverseAccountBalances($journalEntry);

            // Delete journal entry items first
            JournalEntryItem::where('journal_entry_id', $journalEntry->id)->delete();

            // Delete journal entry
            $journalEntry->delete();
        }
    }

    private function updateAccountBalances($journalEntry)
    {
        $journalEntry->load('items.account');

        foreach($journalEntry->items as $item) {
            $account = $item->account;
            $debitAmount = $item->debit_amount;
            $creditAmount = $item->credit_amount;

            if ($account->normal_balance === 'debit') {
                $account->current_balance += ($debitAmount - $creditAmount);
            } else {
                $account->current_balance += ($creditAmount - $debitAmount);
            }

            $account->save();
        }
    }

    private function reverseAccountBalances($journalEntry)
    {
        $journalEntry->load('items.account');

        foreach($journalEntry->items as $item) {
            $account = $item->account;
            $debitAmount = $item->debit_amount;
            $creditAmount = $item->credit_amount;

            // Reverse the balance update
            if ($account->normal_balance === 'debit') {
                $account->current_balance -= ($debitAmount - $creditAmount);
            } else {
                $account->current_balance -= ($creditAmount - $debitAmount);
            }

            $account->save();
        }
    }

    /**
     * Creates journal entry for debit note: Dr: A/P, Cr: Inventory + Tax Receivable
     * Usage: DebitNoteController->approve() after approving debit note
     */
    public function createDebitNoteJournal($debitNote)
    {
        $vatInputTaxCode = $this->getVatInputTaxAccountCode();

        $requiredAccounts = ['2000', '1200'];
        if ($debitNote->tax_amount > 0) {
            $requiredAccounts[] = $vatInputTaxCode;
        }
        $this->validateAccounts($requiredAccounts);

        $totalDebit = $debitNote->total_amount;
        $totalCredit = $debitNote->subtotal  - $debitNote->discount_amount + ($debitNote->tax_amount ?? 0);
        $this->validateBalance($totalDebit, $totalCredit);

        $apAccount = ChartOfAccount::where('account_code', '2000')->where('created_by', creatorId())->first();
        $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatInputTaxCode)->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $debitNote->debit_note_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'debit_note',
            'reference_id' => $debitNote->id,
            'description' => 'Debit Note #' . $debitNote->debit_note_number,
            'total_debit' => $debitNote->total_amount,
            'total_credit' => $debitNote->total_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $apAccount->id,
            'description' => 'Debit Note - ' . $debitNote->vendor->name,
            'debit_amount' => $debitNote->total_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'Goods returned to vendor',
            'debit_amount' => 0,
            'credit_amount' => $debitNote->subtotal - $debitNote->discount_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        if ($debitNote->tax_amount > 0) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Tax credit from debit note',
                'debit_amount' => 0,
                'credit_amount' => $debitNote->tax_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for credit note: Dr: Sales Revenue + Tax, Cr: A/R
     * Usage: CreditNoteController->approve() after approving credit note
     */
    public function createCreditNoteJournal($creditNote)
    {
        $vatOutputTaxCode = $this->getVatOutputTaxAccountCode();

        // Validate required accounts exist
        $requiredAccounts = ['1100', '4100'];
        if ($creditNote->tax_amount > 0) {
            $requiredAccounts[] = $vatOutputTaxCode;
        }
        $this->validateAccounts($requiredAccounts);

        // Validate amounts balance
        $totalDebit = $creditNote->subtotal - $creditNote->discount_amount + ($creditNote->tax_amount ?? 0);
        $totalCredit = $creditNote->total_amount;
        $this->validateBalance($totalDebit, $totalCredit);

        $arAccount = ChartOfAccount::where('account_code', '1100')->where('created_by', creatorId())->first();
        $salesAccount = ChartOfAccount::where('account_code', '4100')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatOutputTaxCode)->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $creditNote->credit_note_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'credit_note',
            'reference_id' => $creditNote->id,
            'description' => 'Credit Note #' . $creditNote->credit_note_number,
            'total_debit' => $creditNote->total_amount,
            'total_credit' => $creditNote->total_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Sales Revenue (reduces revenue)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $salesAccount->id,
            'description' => 'Credit Note adjustment',
            'debit_amount' => $creditNote->subtotal - $creditNote->discount_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Tax Payable (if tax exists)
        if ($creditNote->tax_amount > 0) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Tax reduction from credit note',
                'debit_amount' => $creditNote->tax_amount,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        // Credit: Accounts Receivable (reduces customer debt)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'description' => 'Credit Note - ' . $creditNote->customer->name,
            'debit_amount' => 0,
            'credit_amount' => $creditNote->total_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for bank transfer: Dr: To Bank + Charges, Cr: From Bank
     * Usage: BankTransferController->process() after processing transfer
     */
    public function createBankTransferJournal($bankTransfer)
    {
        // Get the specific bank accounts' GL accounts
        $fromBankGLAccount = $bankTransfer->fromAccount->glAccount;
        if (!$fromBankGLAccount) {
            throw new \Exception( __("Source bank account must have a GL account assigned"));
        }

        $toBankGLAccount = $bankTransfer->toAccount->glAccount;
        if (!$toBankGLAccount) {
            throw new \Exception( __("Destination bank account must have a GL account assigned"));
        }

        $bankChargesAccount = ChartOfAccount::where('account_code', '5510')->where('created_by', creatorId())->first();

        $totalDebit = $bankTransfer->transfer_amount + $bankTransfer->transfer_charges;
        $totalCredit = $bankTransfer->transfer_amount + $bankTransfer->transfer_charges;

        $this->validateBalance($totalDebit, $totalCredit);

        $journalEntry = JournalEntry::create([
            'journal_date' => $bankTransfer->transfer_date,
            'entry_type' => 'automatic',
            'reference_type' => 'bank_transfer',
            'reference_id' => $bankTransfer->id,
            'description' => 'Bank Transfer #' . $bankTransfer->transfer_number,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Destination Bank Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $toBankGLAccount->id,
            'description' => 'Transfer received from ' . $bankTransfer->fromAccount->account_name,
            'debit_amount' => $bankTransfer->transfer_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Bank Charges (if any)
        if ($bankTransfer->transfer_charges > 0 && $bankChargesAccount) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $bankChargesAccount->id,
                'description' => 'Bank transfer charges',
                'debit_amount' => $bankTransfer->transfer_charges,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        // Credit: Source Bank Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $fromBankGLAccount->id,
            'description' => 'Transfer sent to ' . $bankTransfer->toAccount->account_name,
            'debit_amount' => 0,
            'credit_amount' => $totalDebit,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }
    public function createRetainerPaymentJournal($retainerPayment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = BankAccount::where('id', $retainerPayment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate Customer Deposits exists (2350)
        $this->validateAccounts(['2350']);
        $unearnedRevenueAccount = ChartOfAccount::where('account_code', '2350')->where('created_by', creatorId())->first();

        // Validate amounts balance
        $this->validateBalance($retainerPayment->payment_amount, $retainerPayment->payment_amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $retainerPayment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'retainer_payment',
            'reference_id' => $retainerPayment->id,
            'description' => 'Retainer Payment from ' . $retainerPayment->customer->name,
            'total_debit' => $retainerPayment->payment_amount,
            'total_credit' => $retainerPayment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);
        // Debit: Specific Bank Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Retainer advance payment received',
            'debit_amount' => $retainerPayment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Unearned Revenue (Liability)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $unearnedRevenueAccount->id,
            'description' => 'Retainer advance payment received',
            'debit_amount' => 0,
            'credit_amount' => $retainerPayment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);


        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }
    public function createCommissionPaymentJournal($commissionPayment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = BankAccount::where('id', $commissionPayment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate Commission Expense account exists (5220)
        $this->validateAccounts(['5220']);
        $commissionExpenseAccount = ChartOfAccount::where('account_code', '5220')->where('created_by', creatorId())->first();

        // Validate amounts balance
        $this->validateBalance($commissionPayment->payment_amount, $commissionPayment->payment_amount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $commissionPayment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'commission_payment',
            'reference_id' => $commissionPayment->id,
            'description' => 'Commission Payment to ' . $commissionPayment->agent->name,
            'total_debit' => $commissionPayment->payment_amount,
            'total_credit' => $commissionPayment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Debit: Commission Expense
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $commissionExpenseAccount->id,
            'description' => 'Commission paid to agent',
            'debit_amount' => $commissionPayment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Specific Bank Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Commission payment from ' . $bankGLAccount->account_name,
            'debit_amount' => 0,
            'credit_amount' => $commissionPayment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for payroll: Dr: Salary Expense, Cr: Bank
     * Usage: PaySalaryListener after paying salary
     */
    public function createPayrollJournal($payrollEntry)
    {
        $bankGLAccount = BankAccount::where('id', $payrollEntry->payroll->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        $this->validateAccounts(['5200']);
        $salaryExpenseAccount = ChartOfAccount::where('account_code', '5200')->where('created_by', creatorId())->first();
        $this->validateBalance($payrollEntry->net_pay, $payrollEntry->net_pay);

        $journalEntry = JournalEntry::create([
            'journal_date' => now(),
            'entry_type' => 'automatic',
            'reference_type' => 'payroll',
            'reference_id' => $payrollEntry->id,
            'description' => 'Salary Payment - ' . $payrollEntry->employee->user->name,
            'total_debit' => $payrollEntry->net_pay,
            'total_credit' => $payrollEntry->net_pay,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $salaryExpenseAccount->id,
            'description' => 'Salary paid to ' . $payrollEntry->employee->user->name,
            'debit_amount' => $payrollEntry->net_pay,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Salary payment',
            'debit_amount' => 0,
            'credit_amount' => $payrollEntry->net_pay,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for POS sale: Dr: Cash/Bank, Cr: Sales Revenue + Tax
     * Usage: CreatePosListener after POS sale
     */
    public function createPosJournal($posSale)
    {
        $vatOutputTaxCode = $this->getVatOutputTaxAccountCode();

        $bankGLAccount = BankAccount::where('id', $posSale->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        $posSale->load(['payment', 'items']);

        $taxAmount = $posSale->items->sum('tax_amount');
        $requiredAccounts = ['1010', '4100'];
        if ($taxAmount > 0) {
            $requiredAccounts[] = $vatOutputTaxCode;
        }
        $this->validateAccounts($requiredAccounts);

        $salesAccount = ChartOfAccount::where('account_code', '4100')->where('created_by', creatorId())->first();
        $taxAccount = ChartOfAccount::where('account_code', $vatOutputTaxCode)->where('created_by', creatorId())->first();

        $totalAmount = $posSale->payment->discount_amount ?? 0;
        $subtotal = $posSale->items->sum('subtotal');
        $discount = $posSale->payment->discount ?? 0;

        $this->validateBalance($totalAmount, $subtotal - $discount + $taxAmount);

        $journalEntry = JournalEntry::create([
            'journal_date' => $posSale->pos_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'pos_sale',
            'reference_id' => $posSale->id,
            'description' => 'POS Sale ' . $posSale->sale_number,
            'total_debit' => $totalAmount,
            'total_credit' => $totalAmount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'POS cash sale',
            'debit_amount' => $totalAmount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $salesAccount->id,
            'description' => 'POS product sales',
            'debit_amount' => 0,
            'credit_amount' => $subtotal - $discount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        if ($taxAmount > 0 && $taxAccount) {
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'description' => 'Sales tax collected',
                'debit_amount' => 0,
                'credit_amount' => $taxAmount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        }

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates COGS journal entry for sales invoice: Dr: COGS, Cr: Inventory
     * Usage: PostSalesInvoiceListener after creating sales invoice
     */
    public function createSalesCOGSJournal($salesInvoice)
    {
        $salesInvoice->load('items.product');
        $totalCost = 0;

        foreach ($salesInvoice->items as $item) {
            if (!$item->product) {
                continue;
            }
            $costPrice = $item->product->purchase_price ?? 0;
            $totalCost += $item->quantity * $costPrice;
        }

        if ($totalCost <= 0.01) {
            return null;
        }

        $this->validateAccounts(['5100', '1200']);

        $cogsAccount = ChartOfAccount::where('account_code', '5100')->where('created_by', creatorId())->first();
        $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();

        $this->validateBalance($totalCost, $totalCost);

        $journalEntry = JournalEntry::create([
            'journal_date' => $salesInvoice->invoice_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'sales_invoice_cogs',
            'reference_id' => $salesInvoice->id,
            'description' => 'COGS for Sales Invoice #' . $salesInvoice->invoice_number,
            'total_debit' => $totalCost,
            'total_credit' => $totalCost,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $cogsAccount->id,
            'description' => 'Cost of goods sold',
            'debit_amount' => $totalCost,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'Inventory reduction',
            'debit_amount' => 0,
            'credit_amount' => $totalCost,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates COGS journal entry for POS sale: Dr: COGS, Cr: Inventory
     * Usage: CreatePosListener after creating POS sale
     */
    public function createPosCOGSJournal($posSale)
    {
        try {
            $posSale->load('items.product');
            $totalCost = 0;

            foreach ($posSale->items as $item) {
                if (!$item->product) {
                    continue;
                }
                $costPrice = $item->product->purchase_price ?? 0;
                $totalCost += $item->quantity * $costPrice;
            }

            if ($totalCost <= 0.01) {
                return null;
            }

            $this->validateAccounts(['5100', '1200']);

            $cogsAccount = ChartOfAccount::where('account_code', '5100')->where('created_by', creatorId())->first();
            $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();

            $this->validateBalance($totalCost, $totalCost);

            $journalEntry = JournalEntry::create([
                'journal_date' => $posSale->pos_date ?? now(),
                'entry_type' => 'automatic',
                'reference_type' => 'pos_sale_cogs',
                'reference_id' => $posSale->id,
                'description' => 'COGS for POS Sale ' . $posSale->sale_number,
                'total_debit' => $totalCost,
                'total_credit' => $totalCost,
                'status' => 'posted',
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cogsAccount->id,
                'description' => 'Cost of goods sold',
                'debit_amount' => $totalCost,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $inventoryAccount->id,
                'description' => 'Inventory reduction',
                'debit_amount' => 0,
                'credit_amount' => $totalCost,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            $this->updateAccountBalances($journalEntry);
            return $journalEntry;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Creates COGS reversal journal entry for credit note: Dr: Inventory, Cr: COGS
     * Usage: CreditNoteController->approve() after approving credit note
     */
    public function createCreditNoteCOGSJournal($creditNote)
    {
        try {
            $creditNote->load('items.product');
            $totalCost = 0;

            foreach ($creditNote->items as $item) {
                if (!$item->product) {
                    continue;
                }
                $costPrice = $item->product->purchase_price ?? 0;
                $totalCost += $item->quantity * $costPrice;
            }

            if ($totalCost <= 0.01) {
                return null;
            }

            $this->validateAccounts(['5100', '1200']);

            $cogsAccount = ChartOfAccount::where('account_code', '5100')->where('created_by', creatorId())->first();
            $inventoryAccount = ChartOfAccount::where('account_code', '1200')->where('created_by', creatorId())->first();

            $this->validateBalance($totalCost, $totalCost);

            $journalEntry = JournalEntry::create([
                'journal_date' => $creditNote->credit_note_date ?? now(),
                'entry_type' => 'automatic',
                'reference_type' => 'credit_note_cogs',
                'reference_id' => $creditNote->id,
                'description' => 'COGS Reversal for Credit Note #' . $creditNote->credit_note_number,
                'total_debit' => $totalCost,
                'total_credit' => $totalCost,
                'status' => 'posted',
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $inventoryAccount->id,
                'description' => 'Inventory returned',
                'debit_amount' => $totalCost,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cogsAccount->id,
                'description' => 'COGS reversal',
                'debit_amount' => 0,
                'credit_amount' => $totalCost,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            $this->updateAccountBalances($journalEntry);
            return $journalEntry;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Creates journal entry for mobile service payment: Dr: Bank, Cr: Mobile Service Revenue
     * Usage: MobileServiceController->store() after recording mobile service payment
     */
    public function createMobileServicePaymentJournal($payment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = BankAccount::where('id', $payment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate required accounts exist (4200 for Mobile Service Revenue)
        $this->validateAccounts(['4200']);

        // Validate amounts balance
        $this->validateBalance($payment->payment_amount, $payment->payment_amount);

        $mobileServiceRevenueAccount = ChartOfAccount::where('account_code', '4200')->where('created_by', creatorId())->first();
        $journalEntry = JournalEntry::create([
            'journal_date' => $payment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'mobile_service_payment',
            'reference_id' => $payment->id,
            'description' => 'Mobile Service Payment - ' . $payment->notes,
            'total_debit' => $payment->payment_amount,
            'total_credit' => $payment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);
        // Debit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Mobile service payment received',
            'debit_amount' => $payment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Mobile Service Revenue Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $mobileServiceRevenueAccount->id,
            'description' => 'Mobile service revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $payment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for fleet booking payment: Dr: Bank, Cr: Fleet Service Revenue
     * Usage: FleetBookingController->markPayment() after recording fleet booking payment
     */
    public function createMarkFleetBookingPaymentJournal($payment)
    {
        // Get the specific bank account's GL account
        $bankGLAccount = BankAccount::where('id', $payment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        // Validate required accounts exist (4300 for Fleet Service Revenue)
        $this->validateAccounts(['4300']);

        // Validate amounts balance
        $this->validateBalance($payment->payment_amount, $payment->payment_amount);

        $fleetServiceRevenueAccount = ChartOfAccount::where('account_code', '4300')->where('created_by', creatorId())->first();
        $journalEntry = JournalEntry::create([
            'journal_date' => $payment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'fleet_booking_payment',
            'reference_id' => $payment->id,
            'description' => 'Fleet Booking Payment - ' . ($payment->notes ?? 'Fleet service payment'),
            'total_debit' => $payment->payment_amount,
            'total_credit' => $payment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);
        // Debit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Fleet booking payment received',
            'debit_amount' => $payment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // Credit: Fleet Service Revenue Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $fleetServiceRevenueAccount->id,
            'description' => 'Fleet service revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $payment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for beauty booking payment: Dr: Bank, Cr: Beauty Service Revenue
     * Usage: BeautyBookingController->markPayment() after recording beauty booking payment
     */
    public function createBeautyBookingPaymentJournal($booking)
    {
        // Get bank account by payment gateway
        $bankAccount = BankAccount::where('payment_gateway', $booking->payment_option)->where('created_by', $booking->created_by)
            ->first();

        if (!$bankAccount || !$bankAccount->glAccount) {
            throw new \Exception("Bank account with GL account not found for payment gateway: " . $booking->payment_option);
        }

        // Validate required accounts exist (4200 for Service Revenue)
        $this->validateAccounts(['4200'], $booking->created_by);
        // Validate amounts balance
        $this->validateBalance($booking->price, $booking->price);

        $beautyServiceRevenueAccount = ChartOfAccount::where('account_code', '4200')->where('created_by', $booking->created_by)->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $booking->date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'beauty_booking_payment',
            'reference_id' => $booking->id,
            'description' => 'Beauty Booking Payment via ' . $booking->payment_option,
            'total_debit' => $booking->price,
            'total_credit' => $booking->price,
            'status' => 'posted',
            'creator_id' => $booking->created_by,
            'created_by' => $booking->created_by
        ]);
        // Debit: Specific Bank Account (from GL Account)
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankAccount->glAccount->id,
            'description' => 'Beauty booking payment received',
            'debit_amount' => $booking->price,
            'credit_amount' => 0,
            'creator_id' => $booking->created_by,
            'created_by' => $booking->created_by
        ]);

        // Credit: Beauty Service Revenue Account
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $beautyServiceRevenueAccount->id,
            'description' => 'Beauty service revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $booking->price,
            'creator_id' => $booking->created_by,
            'created_by' => $booking->created_by
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    public function createDairyCattlePaymentJournal($dairyCattlePayment)
    {
        $bankGLAccount = BankAccount::where('id', $dairyCattlePayment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }

        $this->validateAccounts(['4200']);
        $this->validateBalance($dairyCattlePayment->payment_amount, $dairyCattlePayment->payment_amount);

        $dairyCattleRevenueAccount = ChartOfAccount::where('account_code', '4200')->where('created_by', creatorId())->first();
        $journalEntry = JournalEntry::create([
            'journal_date' => $dairyCattlePayment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'dairy_cattle_payment',
            'reference_id' => $dairyCattlePayment->id,
            'description' => 'Dairy Cattle Payment - ' . ($dairyCattlePayment->notes ?? 'Dairy cattle service payment'),
            'total_debit' => $dairyCattlePayment->payment_amount,
            'total_credit' => $dairyCattlePayment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Dairy cattle payment received',
            'debit_amount' => $dairyCattlePayment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $dairyCattleRevenueAccount->id,
            'description' => 'Dairy cattle service revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $dairyCattlePayment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    public function createCateringOrderPaymentJournal($payment) {

        $bankGLAccount = BankAccount::where('id', $payment->bank_account_id)->first()->glAccount;
        if (!$bankGLAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }
        $this->validateAccounts(['4200']);
        $this->validateBalance($payment->amount, $payment->amount);

        $cateringServiceRevenueAccount = ChartOfAccount::where('account_code', '4200')->where('created_by', creatorId())->first();
        $journalEntry = JournalEntry::create([
            'journal_date' => $payment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'catering_order_payment',
            'reference_id' => $payment->id,
            'description' => 'Catering Order Payment - ' . ($payment->notes ?? 'Catering service payment'),
            'total_debit' => $payment->amount,
            'total_credit' => $payment->amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Catering order payment received',
            'debit_amount' => $payment->amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $cateringServiceRevenueAccount->id,
            'description' => 'Catering service revenue earned',
            'debit_amount' => 0,
            'credit_amount' => $payment->amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    public function createUpdateSalesAgentCommissionPaymentJournal($payment)
    {
        $bankAccount = BankAccount::where('id', $payment->bank_account_id)->first();
        if (!$bankAccount || !$bankAccount->glAccount) {
            throw new \Exception("Bank account must have a GL account assigned");
        }
        $bankGLAccount = $bankAccount->glAccount;

        $this->validateAccounts(['5220']);
        $this->validateBalance($payment->payment_amount, $payment->payment_amount);

        $commissionExpenseAccount = ChartOfAccount::where('account_code', '5220')->where('created_by', creatorId())->first();
        $journalEntry = JournalEntry::create([
            'journal_date' => $payment->payment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'sales_agent_commission_payment',
            'reference_id' => $payment->id,
            'description' => 'Commission Payment #' . $payment->payment_number . ' - ' . $payment->agent->user->name,
            'total_debit' => $payment->payment_amount,
            'total_credit' => $payment->payment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);
        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $commissionExpenseAccount->id,
            'description' => 'Commission expense - ' . $payment->agent->user->name,
            'debit_amount' => $payment->payment_amount,
            'credit_amount' => 0,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankGLAccount->id,
            'description' => 'Payment from ' . $bankAccount->account_name,
            'debit_amount' => 0,
            'credit_amount' => $payment->payment_amount,
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

    /**
     * Creates journal entry for commission adjustment
     * Bonus = Company Expense, Penalty = Company Income
     * Usage: ApproveSalesAgentCommissionAdjustment listener
     */
    public function createCommissionAdjustmentJournal($adjustment)
    {
        $this->validateAccounts(['5220', '2400', '4300']);
        $this->validateBalance($adjustment->adjustment_amount, $adjustment->adjustment_amount);

        $commissionExpenseAccount = ChartOfAccount::where('account_code', '5220')->where('created_by', creatorId())->first();
        $commissionPayableAccount = ChartOfAccount::where('account_code', '2400')->where('created_by', creatorId())->first();
        $otherIncomeAccount = ChartOfAccount::where('account_code', '4300')->where('created_by', creatorId())->first();

        $journalEntry = JournalEntry::create([
            'journal_date' => $adjustment->adjustment_date ?? now(),
            'entry_type' => 'automatic',
            'reference_type' => 'commission_adjustment',
            'reference_id' => $adjustment->id,
            'description' => 'Commission Adjustment (' . ucfirst($adjustment->adjustment_type) . ') - ' . $adjustment->agent->user->name,
            'total_debit' => $adjustment->adjustment_amount,
            'total_credit' => $adjustment->adjustment_amount,
            'status' => 'posted',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        if ($adjustment->adjustment_type === 'bonus') {
            // Bonus = Company Expense
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $commissionExpenseAccount->id,
                'description' => 'Commission bonus - ' . $adjustment->adjustment_reason,
                'debit_amount' => $adjustment->adjustment_amount,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $commissionPayableAccount->id,
                'description' => 'Commission payable to agent',
                'debit_amount' => 0,
                'credit_amount' => $adjustment->adjustment_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        } elseif ($adjustment->adjustment_type === 'penalty') {
            // Penalty = Company Income
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $commissionPayableAccount->id,
                'description' => 'Commission deduction from agent',
                'debit_amount' => $adjustment->adjustment_amount,
                'credit_amount' => 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $otherIncomeAccount->id,
                'description' => 'Commission penalty - ' . $adjustment->adjustment_reason,
                'debit_amount' => 0,
                'credit_amount' => $adjustment->adjustment_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);
        } else {
            // Correction = Can be positive or negative
            $amount = abs($adjustment->adjustment_amount);

            if ($adjustment->adjustment_amount > 0) {
                // Positive correction = Increase expense
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $commissionExpenseAccount->id,
                    'description' => 'Commission correction - ' . $adjustment->adjustment_reason,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);

                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $commissionPayableAccount->id,
                    'description' => 'Commission payable adjustment',
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);
            } else {
                // Negative correction = Decrease expense
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $commissionPayableAccount->id,
                    'description' => 'Commission payable adjustment',
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);

                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $commissionExpenseAccount->id,
                    'description' => 'Commission correction - ' . $adjustment->adjustment_reason,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);
            }
        }

        try {
            UpdateBudgetSpending::dispatch($journalEntry);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->updateAccountBalances($journalEntry);
        return $journalEntry;
    }

}
