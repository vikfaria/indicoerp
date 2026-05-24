<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('chart_of_accounts', 'is_movement_account')) {
                $table->boolean('is_movement_account')->default(true)->after('is_system_account')
                    ->comment('Only movement accounts accept journal entries');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'pgc_class')) {
                $table->unsignedTinyInteger('pgc_class')->nullable()->after('is_movement_account')
                    ->comment('PGC-MZ class 0-9');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'tax_type')) {
                $table->string('tax_type', 30)->nullable()->after('pgc_class')
                    ->comment('vat_output, vat_input, irpc, irps, inss, withholding, none');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'vat_code')) {
                $table->string('vat_code', 20)->nullable()->after('tax_type')
                    ->comment('Reference to mz_vat_codes');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'deductibility')) {
                $table->enum('deductibility', ['full', 'partial', 'none'])->nullable()->after('vat_code');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'financial_statement_line')) {
                $table->string('financial_statement_line', 50)->nullable()->after('deductibility')
                    ->comment('Line reference for balance sheet / P&L');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'modelo20_line')) {
                $table->string('modelo20_line', 50)->nullable()->after('financial_statement_line');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'saft_taxonomy_code')) {
                $table->string('saft_taxonomy_code', 20)->nullable()->after('modelo20_line');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'cost_center_required')) {
                $table->boolean('cost_center_required')->default(false)->after('saft_taxonomy_code');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'accounting_framework')) {
                $table->string('accounting_framework', 20)->nullable()->after('cost_center_required')
                    ->comment('pgc_nirf, pgc_pe, ispc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $columns = [
                'is_movement_account', 'pgc_class', 'tax_type', 'vat_code',
                'deductibility', 'financial_statement_line', 'modelo20_line',
                'saft_taxonomy_code', 'cost_center_required', 'accounting_framework',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('chart_of_accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
