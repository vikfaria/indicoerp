<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->indexAccountingPeriods();
        $this->indexAccountingJournals();
        $this->indexFiscalDocumentSeries();
        $this->indexMonthlyClosingChecklists();
        $this->indexDepreciationEntries();
        $this->indexStockMovements();
        $this->indexStockCostLayers();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('accounting_periods', 'ap_company_status_idx');
        $this->dropIndexIfExists('accounting_journals', 'aj_company_active_type_idx');
        $this->dropIndexIfExists('fiscal_document_series', 'fds_company_type_year_active_idx');
        $this->dropIndexIfExists('monthly_closing_checklists', 'mcc_company_period_status_idx');
        $this->dropIndexIfExists('depreciation_entries', 'de_asset_year_period_idx');
        $this->dropIndexIfExists('stock_movements', 'sm_company_ref_idx');
        $this->dropIndexIfExists('stock_cost_layers', 'scl_company_product_exhausted_entry_idx');
    }

    private function indexAccountingPeriods(): void
    {
        if (!Schema::hasTable('accounting_periods')) {
            return;
        }

        Schema::table('accounting_periods', function (Blueprint $table): void {
            if (
                !$this->indexExists('accounting_periods', 'ap_company_status_idx')
                && $this->hasColumns('accounting_periods', ['company_id', 'status'])
            ) {
                $table->index(['company_id', 'status'], 'ap_company_status_idx');
            }
        });
    }

    private function indexAccountingJournals(): void
    {
        if (!Schema::hasTable('accounting_journals')) {
            return;
        }

        Schema::table('accounting_journals', function (Blueprint $table): void {
            if (
                !$this->indexExists('accounting_journals', 'aj_company_active_type_idx')
                && $this->hasColumns('accounting_journals', ['company_id', 'is_active', 'type'])
            ) {
                $table->index(['company_id', 'is_active', 'type'], 'aj_company_active_type_idx');
            }
        });
    }

    private function indexFiscalDocumentSeries(): void
    {
        if (!Schema::hasTable('fiscal_document_series')) {
            return;
        }

        Schema::table('fiscal_document_series', function (Blueprint $table): void {
            if (
                !$this->indexExists('fiscal_document_series', 'fds_company_type_year_active_idx')
                && $this->hasColumns('fiscal_document_series', ['company_id', 'fiscal_document_type_id', 'fiscal_year', 'is_active'])
            ) {
                $table->index(
                    ['company_id', 'fiscal_document_type_id', 'fiscal_year', 'is_active'],
                    'fds_company_type_year_active_idx'
                );
            }
        });
    }

    private function indexMonthlyClosingChecklists(): void
    {
        if (!Schema::hasTable('monthly_closing_checklists')) {
            return;
        }

        Schema::table('monthly_closing_checklists', function (Blueprint $table): void {
            if (
                !$this->indexExists('monthly_closing_checklists', 'mcc_company_period_status_idx')
                && $this->hasColumns('monthly_closing_checklists', ['company_id', 'accounting_period_id', 'status'])
            ) {
                $table->index(['company_id', 'accounting_period_id', 'status'], 'mcc_company_period_status_idx');
            }
        });
    }

    private function indexDepreciationEntries(): void
    {
        if (!Schema::hasTable('depreciation_entries')) {
            return;
        }

        Schema::table('depreciation_entries', function (Blueprint $table): void {
            if (
                !$this->indexExists('depreciation_entries', 'de_asset_year_period_idx')
                && $this->hasColumns('depreciation_entries', ['fixed_asset_id', 'fiscal_year', 'period_number'])
            ) {
                $table->index(['fixed_asset_id', 'fiscal_year', 'period_number'], 'de_asset_year_period_idx');
            }
        });
    }

    private function indexStockMovements(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table): void {
            if (
                !$this->indexExists('stock_movements', 'sm_company_ref_idx')
                && $this->hasColumns('stock_movements', ['company_id', 'reference_type', 'reference_id'])
            ) {
                $table->index(['company_id', 'reference_type', 'reference_id'], 'sm_company_ref_idx');
            }
        });
    }

    private function indexStockCostLayers(): void
    {
        if (!Schema::hasTable('stock_cost_layers')) {
            return;
        }

        Schema::table('stock_cost_layers', function (Blueprint $table): void {
            if (
                !$this->indexExists('stock_cost_layers', 'scl_company_product_exhausted_entry_idx')
                && $this->hasColumns('stock_cost_layers', ['company_id', 'product_id', 'is_exhausted', 'entry_date'])
            ) {
                $table->index(
                    ['company_id', 'product_id', 'is_exhausted', 'entry_date'],
                    'scl_company_product_exhausted_entry_idx'
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

