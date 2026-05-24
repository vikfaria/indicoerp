<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add fiscal hash fields to sales_invoices (if it exists)
        $invoiceTables = ['sales_invoices', 'invoices'];
        foreach ($invoiceTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'fiscal_hash')) {
                        $table->string('fiscal_hash', 64)->nullable()->after('id');
                    }
                    if (!Schema::hasColumn($tableName, 'fiscal_hash_control')) {
                        $table->string('fiscal_hash_control', 4)->nullable()->after('fiscal_hash');
                    }
                    if (!Schema::hasColumn($tableName, 'fiscal_document_type_id')) {
                        $table->unsignedBigInteger('fiscal_document_type_id')->nullable()->after('fiscal_hash_control');
                    }
                    if (!Schema::hasColumn($tableName, 'fiscal_series_id')) {
                        $table->unsignedBigInteger('fiscal_series_id')->nullable()->after('fiscal_document_type_id');
                    }
                    if (!Schema::hasColumn($tableName, 'document_sequence')) {
                        $table->unsignedInteger('document_sequence')->nullable()->after('fiscal_series_id');
                    }
                    if (!Schema::hasColumn($tableName, 'document_series')) {
                        $table->string('document_series', 20)->nullable()->after('document_sequence');
                    }
                });
            }
        }

        // Add fiscal hash fields to credit_notes
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_notes', 'fiscal_hash')) {
                    $table->string('fiscal_hash', 64)->nullable()->after('id');
                }
                if (!Schema::hasColumn('credit_notes', 'fiscal_hash_control')) {
                    $table->string('fiscal_hash_control', 4)->nullable()->after('fiscal_hash');
                }
                if (!Schema::hasColumn('credit_notes', 'document_sequence')) {
                    $table->unsignedInteger('document_sequence')->nullable();
                }
                if (!Schema::hasColumn('credit_notes', 'document_series')) {
                    $table->string('document_series', 20)->nullable();
                }
            });
        }

        // Add fiscal hash fields to debit_notes
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('debit_notes', 'fiscal_hash')) {
                    $table->string('fiscal_hash', 64)->nullable()->after('id');
                }
                if (!Schema::hasColumn('debit_notes', 'fiscal_hash_control')) {
                    $table->string('fiscal_hash_control', 4)->nullable()->after('fiscal_hash');
                }
                if (!Schema::hasColumn('debit_notes', 'document_sequence')) {
                    $table->unsignedInteger('document_sequence')->nullable();
                }
                if (!Schema::hasColumn('debit_notes', 'document_series')) {
                    $table->string('document_series', 20)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['sales_invoices', 'invoices', 'credit_notes', 'debit_notes'];
        $columns = ['fiscal_hash', 'fiscal_hash_control', 'fiscal_document_type_id', 'fiscal_series_id', 'document_sequence', 'document_series'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                    foreach ($columns as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }
};
