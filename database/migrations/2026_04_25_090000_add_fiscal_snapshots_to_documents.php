<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'sales_invoices',
            'purchase_invoices',
            'sales_proposals',
            'sales_invoice_returns',
            'purchase_returns',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'issuer_snapshot')) {
                    $table->json('issuer_snapshot')->nullable()->after('created_by');
                }

                if (!Schema::hasColumn($tableName, 'counterparty_snapshot')) {
                    $table->json('counterparty_snapshot')->nullable()->after('issuer_snapshot');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'sales_invoices',
            'purchase_invoices',
            'sales_proposals',
            'sales_invoice_returns',
            'purchase_returns',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = [];

                if (Schema::hasColumn($tableName, 'issuer_snapshot')) {
                    $columns[] = 'issuer_snapshot';
                }

                if (Schema::hasColumn($tableName, 'counterparty_snapshot')) {
                    $columns[] = 'counterparty_snapshot';
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
