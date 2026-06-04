<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payments', 'contract_reference')) {
                $table->string('contract_reference', 255)->nullable()->after('fx_authorization_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'invoice_reference')) {
                $table->string('invoice_reference', 255)->nullable()->after('contract_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'bank_settlement_reference')) {
                $table->string('bank_settlement_reference', 255)->nullable()->after('invoice_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'withholding_receipt_reference')) {
                $table->string('withholding_receipt_reference', 255)->nullable()->after('bank_settlement_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'correspondence_reference')) {
                $table->string('correspondence_reference', 255)->nullable()->after('withholding_receipt_reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        $columns = [
            'contract_reference',
            'invoice_reference',
            'bank_settlement_reference',
            'withholding_receipt_reference',
            'correspondence_reference',
        ];

        $dropColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('vendor_payments', $column)));

        if ($dropColumns === []) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table) use ($dropColumns): void {
            $table->dropColumn($dropColumns);
        });
    }
};
