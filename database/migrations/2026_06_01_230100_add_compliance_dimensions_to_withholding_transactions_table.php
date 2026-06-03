<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            return;
        }

        Schema::table('withholding_tax_transactions', function (Blueprint $table): void {
            if (!Schema::hasColumn('withholding_tax_transactions', 'source_reference_type')) {
                $table->string('source_reference_type', 50)->nullable()->after('document_reference');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'source_reference_id')) {
                $table->unsignedBigInteger('source_reference_id')->nullable()->after('source_reference_type');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'beneficiary_country')) {
                $table->string('beneficiary_country', 120)->nullable()->after('vendor_name');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'beneficiary_residency_status')) {
                $table->string('beneficiary_residency_status', 20)->nullable()->after('beneficiary_country');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'income_type_snapshot')) {
                $table->string('income_type_snapshot', 50)->nullable()->after('beneficiary_residency_status');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'withholding_treatment')) {
                $table->string('withholding_treatment', 30)->nullable()->after('withholding_rate');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'adt_applied')) {
                $table->boolean('adt_applied')->default(false)->after('withholding_treatment');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'adt_certificate_reference')) {
                $table->string('adt_certificate_reference', 120)->nullable()->after('adt_applied');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'fiscal_compliance_reference')) {
                $table->string('fiscal_compliance_reference', 120)->nullable()->after('adt_certificate_reference');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'financial_approval_reference')) {
                $table->string('financial_approval_reference', 120)->nullable()->after('fiscal_compliance_reference');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'fx_authorization_reference')) {
                $table->string('fx_authorization_reference', 120)->nullable()->after('financial_approval_reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            return;
        }

        Schema::table('withholding_tax_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('withholding_tax_transactions', 'fx_authorization_reference')) {
                $table->dropColumn('fx_authorization_reference');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'financial_approval_reference')) {
                $table->dropColumn('financial_approval_reference');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'fiscal_compliance_reference')) {
                $table->dropColumn('fiscal_compliance_reference');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'adt_certificate_reference')) {
                $table->dropColumn('adt_certificate_reference');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'adt_applied')) {
                $table->dropColumn('adt_applied');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'withholding_treatment')) {
                $table->dropColumn('withholding_treatment');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'income_type_snapshot')) {
                $table->dropColumn('income_type_snapshot');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'beneficiary_residency_status')) {
                $table->dropColumn('beneficiary_residency_status');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'beneficiary_country')) {
                $table->dropColumn('beneficiary_country');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'source_reference_id')) {
                $table->dropColumn('source_reference_id');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'source_reference_type')) {
                $table->dropColumn('source_reference_type');
            }
        });
    }
};

