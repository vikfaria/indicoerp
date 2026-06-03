<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('customer_payments', 'is_export_receipt')) {
                $table->boolean('is_export_receipt')
                    ->default(false)
                    ->after('fx_difference_amount');
            }

            if (!Schema::hasColumn('customer_payments', 'receipt_origin_country')) {
                $table->string('receipt_origin_country', 120)
                    ->nullable()
                    ->after('is_export_receipt');
            }

            if (!Schema::hasColumn('customer_payments', 'export_reference')) {
                $table->string('export_reference', 120)
                    ->nullable()
                    ->after('receipt_origin_country');
            }

            if (!Schema::hasColumn('customer_payments', 'intermediary_bank')) {
                $table->string('intermediary_bank', 120)
                    ->nullable()
                    ->after('export_reference');
            }

            if (!Schema::hasColumn('customer_payments', 'repatriation_status')) {
                $table->string('repatriation_status', 30)
                    ->default('not_applicable')
                    ->after('intermediary_bank');
            }

            if (!Schema::hasColumn('customer_payments', 'repatriated_amount_mzn')) {
                $table->decimal('repatriated_amount_mzn', 15, 2)
                    ->nullable()
                    ->after('repatriation_status');
            }

            if (!Schema::hasColumn('customer_payments', 'fx_compliance_reference')) {
                $table->string('fx_compliance_reference', 120)
                    ->nullable()
                    ->after('repatriated_amount_mzn');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_payments', 'fx_compliance_reference')) {
                $table->dropColumn('fx_compliance_reference');
            }

            if (Schema::hasColumn('customer_payments', 'repatriated_amount_mzn')) {
                $table->dropColumn('repatriated_amount_mzn');
            }

            if (Schema::hasColumn('customer_payments', 'repatriation_status')) {
                $table->dropColumn('repatriation_status');
            }

            if (Schema::hasColumn('customer_payments', 'intermediary_bank')) {
                $table->dropColumn('intermediary_bank');
            }

            if (Schema::hasColumn('customer_payments', 'export_reference')) {
                $table->dropColumn('export_reference');
            }

            if (Schema::hasColumn('customer_payments', 'receipt_origin_country')) {
                $table->dropColumn('receipt_origin_country');
            }

            if (Schema::hasColumn('customer_payments', 'is_export_receipt')) {
                $table->dropColumn('is_export_receipt');
            }
        });
    }
};
