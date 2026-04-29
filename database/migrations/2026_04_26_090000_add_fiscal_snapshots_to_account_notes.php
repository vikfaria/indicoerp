<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_notes', 'issuer_snapshot')) {
                    $table->json('issuer_snapshot')->nullable()->after('created_by');
                }

                if (!Schema::hasColumn('credit_notes', 'counterparty_snapshot')) {
                    $table->json('counterparty_snapshot')->nullable()->after('issuer_snapshot');
                }
            });
        }

        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('debit_notes', 'issuer_snapshot')) {
                    $table->json('issuer_snapshot')->nullable()->after('created_by');
                }

                if (!Schema::hasColumn('debit_notes', 'counterparty_snapshot')) {
                    $table->json('counterparty_snapshot')->nullable()->after('issuer_snapshot');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (Schema::hasColumn('credit_notes', 'counterparty_snapshot')) {
                    $table->dropColumn('counterparty_snapshot');
                }

                if (Schema::hasColumn('credit_notes', 'issuer_snapshot')) {
                    $table->dropColumn('issuer_snapshot');
                }
            });
        }

        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (Schema::hasColumn('debit_notes', 'counterparty_snapshot')) {
                    $table->dropColumn('counterparty_snapshot');
                }

                if (Schema::hasColumn('debit_notes', 'issuer_snapshot')) {
                    $table->dropColumn('issuer_snapshot');
                }
            });
        }
    }
};
