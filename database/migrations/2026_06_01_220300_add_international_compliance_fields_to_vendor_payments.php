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
            if (!Schema::hasColumn('vendor_payments', 'is_international_payment')) {
                $table->boolean('is_international_payment')
                    ->default(false)
                    ->after('fx_difference_amount');
            }

            if (!Schema::hasColumn('vendor_payments', 'beneficiary_country')) {
                $table->string('beneficiary_country', 120)
                    ->nullable()
                    ->after('is_international_payment');
            }

            if (!Schema::hasColumn('vendor_payments', 'service_type')) {
                $table->string('service_type', 120)
                    ->nullable()
                    ->after('beneficiary_country');
            }

            if (!Schema::hasColumn('vendor_payments', 'withholding_tax_treatment')) {
                $table->string('withholding_tax_treatment', 30)
                    ->nullable()
                    ->after('service_type');
            }

            if (!Schema::hasColumn('vendor_payments', 'withholding_tax_rate')) {
                $table->decimal('withholding_tax_rate', 8, 4)
                    ->nullable()
                    ->after('withholding_tax_treatment');
            }

            if (!Schema::hasColumn('vendor_payments', 'withholding_tax_amount')) {
                $table->decimal('withholding_tax_amount', 15, 2)
                    ->nullable()
                    ->after('withholding_tax_rate');
            }

            if (!Schema::hasColumn('vendor_payments', 'withholding_exemption_reason')) {
                $table->string('withholding_exemption_reason', 255)
                    ->nullable()
                    ->after('withholding_tax_amount');
            }

            if (!Schema::hasColumn('vendor_payments', 'adt_certificate_reference')) {
                $table->string('adt_certificate_reference', 120)
                    ->nullable()
                    ->after('withholding_exemption_reason');
            }

            if (!Schema::hasColumn('vendor_payments', 'fiscal_compliance_reference')) {
                $table->string('fiscal_compliance_reference', 120)
                    ->nullable()
                    ->after('adt_certificate_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'financial_approval_reference')) {
                $table->string('financial_approval_reference', 120)
                    ->nullable()
                    ->after('fiscal_compliance_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'fx_authorization_reference')) {
                $table->string('fx_authorization_reference', 120)
                    ->nullable()
                    ->after('financial_approval_reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_payments', 'fx_authorization_reference')) {
                $table->dropColumn('fx_authorization_reference');
            }

            if (Schema::hasColumn('vendor_payments', 'financial_approval_reference')) {
                $table->dropColumn('financial_approval_reference');
            }

            if (Schema::hasColumn('vendor_payments', 'fiscal_compliance_reference')) {
                $table->dropColumn('fiscal_compliance_reference');
            }

            if (Schema::hasColumn('vendor_payments', 'adt_certificate_reference')) {
                $table->dropColumn('adt_certificate_reference');
            }

            if (Schema::hasColumn('vendor_payments', 'withholding_exemption_reason')) {
                $table->dropColumn('withholding_exemption_reason');
            }

            if (Schema::hasColumn('vendor_payments', 'withholding_tax_amount')) {
                $table->dropColumn('withholding_tax_amount');
            }

            if (Schema::hasColumn('vendor_payments', 'withholding_tax_rate')) {
                $table->dropColumn('withholding_tax_rate');
            }

            if (Schema::hasColumn('vendor_payments', 'withholding_tax_treatment')) {
                $table->dropColumn('withholding_tax_treatment');
            }

            if (Schema::hasColumn('vendor_payments', 'service_type')) {
                $table->dropColumn('service_type');
            }

            if (Schema::hasColumn('vendor_payments', 'beneficiary_country')) {
                $table->dropColumn('beneficiary_country');
            }

            if (Schema::hasColumn('vendor_payments', 'is_international_payment')) {
                $table->dropColumn('is_international_payment');
            }
        });
    }
};

