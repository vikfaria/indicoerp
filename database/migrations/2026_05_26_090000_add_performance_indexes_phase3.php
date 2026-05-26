<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->indexSalesInvoices();
        $this->indexSalesInvoiceReturns();
        $this->indexPurchaseInvoices();
        $this->indexPurchaseReturns();
        $this->indexCustomerPayments();
        $this->indexVendorPayments();
        $this->indexCreditNotes();
        $this->indexDebitNotes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('sales_invoices', 'si_created_customer_status_date_idx');
        $this->dropIndexIfExists('sales_invoice_returns', 'sir_created_customer_status_date_idx');
        $this->dropIndexIfExists('purchase_invoices', 'pi_created_vendor_status_date_idx');
        $this->dropIndexIfExists('purchase_returns', 'pr_created_vendor_status_date_idx');
        $this->dropIndexIfExists('customer_payments', 'cp_created_customer_date_idx');
        $this->dropIndexIfExists('vendor_payments', 'vp_created_vendor_date_idx');
        $this->dropIndexIfExists('credit_notes', 'cn_created_customer_status_date_idx');
        $this->dropIndexIfExists('debit_notes', 'dn_created_vendor_status_date_idx');
    }

    private function indexSalesInvoices(): void
    {
        if (!Schema::hasTable('sales_invoices')) {
            return;
        }

        Schema::table('sales_invoices', function (Blueprint $table): void {
            if (
                !$this->indexExists('sales_invoices', 'si_created_customer_status_date_idx')
                && $this->hasColumns('sales_invoices', ['created_by', 'customer_id', 'status', 'invoice_date'])
            ) {
                $table->index(
                    ['created_by', 'customer_id', 'status', 'invoice_date'],
                    'si_created_customer_status_date_idx'
                );
            }
        });
    }

    private function indexSalesInvoiceReturns(): void
    {
        if (!Schema::hasTable('sales_invoice_returns')) {
            return;
        }

        Schema::table('sales_invoice_returns', function (Blueprint $table): void {
            if (
                !$this->indexExists('sales_invoice_returns', 'sir_created_customer_status_date_idx')
                && $this->hasColumns('sales_invoice_returns', ['created_by', 'customer_id', 'status', 'return_date'])
            ) {
                $table->index(
                    ['created_by', 'customer_id', 'status', 'return_date'],
                    'sir_created_customer_status_date_idx'
                );
            }
        });
    }

    private function indexPurchaseInvoices(): void
    {
        if (!Schema::hasTable('purchase_invoices')) {
            return;
        }

        Schema::table('purchase_invoices', function (Blueprint $table): void {
            if (
                !$this->indexExists('purchase_invoices', 'pi_created_vendor_status_date_idx')
                && $this->hasColumns('purchase_invoices', ['created_by', 'vendor_id', 'status', 'invoice_date'])
            ) {
                $table->index(
                    ['created_by', 'vendor_id', 'status', 'invoice_date'],
                    'pi_created_vendor_status_date_idx'
                );
            }
        });
    }

    private function indexPurchaseReturns(): void
    {
        if (!Schema::hasTable('purchase_returns')) {
            return;
        }

        Schema::table('purchase_returns', function (Blueprint $table): void {
            if (
                !$this->indexExists('purchase_returns', 'pr_created_vendor_status_date_idx')
                && $this->hasColumns('purchase_returns', ['created_by', 'vendor_id', 'status', 'return_date'])
            ) {
                $table->index(
                    ['created_by', 'vendor_id', 'status', 'return_date'],
                    'pr_created_vendor_status_date_idx'
                );
            }
        });
    }

    private function indexCustomerPayments(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table): void {
            if (
                !$this->indexExists('customer_payments', 'cp_created_customer_date_idx')
                && $this->hasColumns('customer_payments', ['created_by', 'customer_id', 'payment_date'])
            ) {
                $table->index(
                    ['created_by', 'customer_id', 'payment_date'],
                    'cp_created_customer_date_idx'
                );
            }
        });
    }

    private function indexVendorPayments(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (
                !$this->indexExists('vendor_payments', 'vp_created_vendor_date_idx')
                && $this->hasColumns('vendor_payments', ['created_by', 'vendor_id', 'payment_date'])
            ) {
                $table->index(
                    ['created_by', 'vendor_id', 'payment_date'],
                    'vp_created_vendor_date_idx'
                );
            }
        });
    }

    private function indexCreditNotes(): void
    {
        if (!Schema::hasTable('credit_notes')) {
            return;
        }

        Schema::table('credit_notes', function (Blueprint $table): void {
            if (
                !$this->indexExists('credit_notes', 'cn_created_customer_status_date_idx')
                && $this->hasColumns('credit_notes', ['created_by', 'customer_id', 'status', 'credit_note_date'])
            ) {
                $table->index(
                    ['created_by', 'customer_id', 'status', 'credit_note_date'],
                    'cn_created_customer_status_date_idx'
                );
            }
        });
    }

    private function indexDebitNotes(): void
    {
        if (!Schema::hasTable('debit_notes')) {
            return;
        }

        Schema::table('debit_notes', function (Blueprint $table): void {
            if (
                !$this->indexExists('debit_notes', 'dn_created_vendor_status_date_idx')
                && $this->hasColumns('debit_notes', ['created_by', 'vendor_id', 'status', 'debit_note_date'])
            ) {
                $table->index(
                    ['created_by', 'vendor_id', 'status', 'debit_note_date'],
                    'dn_created_vendor_status_date_idx'
                );
            }
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }
};

