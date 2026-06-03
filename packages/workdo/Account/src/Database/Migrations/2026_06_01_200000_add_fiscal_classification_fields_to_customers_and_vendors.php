<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCustomerColumns();
        $this->addVendorColumns();
    }

    public function down(): void
    {
        $this->dropCustomerColumns();
        $this->dropVendorColumns();
    }

    private function addCustomerColumns(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            if (!Schema::hasColumn('customers', 'fiscal_residency_status')) {
                $table->enum('fiscal_residency_status', ['resident', 'non_resident'])
                    ->default('resident')
                    ->after('tax_number')
                    ->index('customers_fiscal_residency_status_idx');
            }

            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->enum('customer_type', ['consumer_final', 'public_entity', 'private_company', 'exempt', 'special_regime'])
                    ->nullable()
                    ->after('fiscal_residency_status');
            }

            if (!Schema::hasColumn('customers', 'fiscal_country')) {
                $table->string('fiscal_country', 120)->nullable()->after('customer_type');
            }

            if (!Schema::hasColumn('customers', 'vat_regime')) {
                $table->string('vat_regime', 50)->nullable()->after('fiscal_country');
            }

            if (!Schema::hasColumn('customers', 'operation_type')) {
                $table->string('operation_type', 50)->nullable()->after('vat_regime');
            }

            if (!Schema::hasColumn('customers', 'billing_currency_code')) {
                $table->string('billing_currency_code', 3)->nullable()->after('operation_type');
            }

            if (!Schema::hasColumn('customers', 'accounting_account_code')) {
                $table->string('accounting_account_code', 50)->nullable()->after('billing_currency_code');
            }

            if (!Schema::hasColumn('customers', 'fiscal_identity_locked_at')) {
                $table->timestamp('fiscal_identity_locked_at')->nullable()->after('accounting_account_code');
            }

            if (!Schema::hasColumn('customers', 'fiscal_identity_lock_reason')) {
                $table->string('fiscal_identity_lock_reason', 255)->nullable()->after('fiscal_identity_locked_at');
            }
        });
    }

    private function addVendorColumns(): void
    {
        if (!Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendors', 'fiscal_residency_status')) {
                $table->enum('fiscal_residency_status', ['resident', 'non_resident'])
                    ->default('resident')
                    ->after('tax_number')
                    ->index('vendors_fiscal_residency_status_idx');
            }

            if (!Schema::hasColumn('vendors', 'vendor_type')) {
                $table->enum('vendor_type', ['public_entity', 'private_company', 'service_provider', 'import_supplier', 'exempt', 'special_regime'])
                    ->nullable()
                    ->after('fiscal_residency_status');
            }

            if (!Schema::hasColumn('vendors', 'fiscal_country')) {
                $table->string('fiscal_country', 120)->nullable()->after('vendor_type');
            }

            if (!Schema::hasColumn('vendors', 'vat_regime')) {
                $table->string('vat_regime', 50)->nullable()->after('fiscal_country');
            }

            if (!Schema::hasColumn('vendors', 'supply_type')) {
                $table->string('supply_type', 50)->nullable()->after('vat_regime');
            }

            if (!Schema::hasColumn('vendors', 'payment_currency_code')) {
                $table->string('payment_currency_code', 3)->nullable()->after('supply_type');
            }

            if (!Schema::hasColumn('vendors', 'foreign_tax_number')) {
                $table->string('foreign_tax_number', 255)->nullable()->after('payment_currency_code');
            }

            if (!Schema::hasColumn('vendors', 'withholding_tax_applicable')) {
                $table->boolean('withholding_tax_applicable')->default(false)->after('foreign_tax_number');
            }

            if (!Schema::hasColumn('vendors', 'reverse_charge_applicable')) {
                $table->boolean('reverse_charge_applicable')->default(false)->after('withholding_tax_applicable');
            }

            if (!Schema::hasColumn('vendors', 'adt_eligible')) {
                $table->boolean('adt_eligible')->default(false)->after('reverse_charge_applicable');
            }

            if (!Schema::hasColumn('vendors', 'adt_country')) {
                $table->string('adt_country', 120)->nullable()->after('adt_eligible');
            }

            if (!Schema::hasColumn('vendors', 'compliance_documents')) {
                $table->json('compliance_documents')->nullable()->after('adt_country');
            }

            if (!Schema::hasColumn('vendors', 'fiscal_identity_locked_at')) {
                $table->timestamp('fiscal_identity_locked_at')->nullable()->after('compliance_documents');
            }

            if (!Schema::hasColumn('vendors', 'fiscal_identity_lock_reason')) {
                $table->string('fiscal_identity_lock_reason', 255)->nullable()->after('fiscal_identity_locked_at');
            }
        });
    }

    private function dropCustomerColumns(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $columns = [
            'fiscal_residency_status',
            'customer_type',
            'fiscal_country',
            'vat_regime',
            'operation_type',
            'billing_currency_code',
            'accounting_account_code',
            'fiscal_identity_locked_at',
            'fiscal_identity_lock_reason',
        ];

        $dropColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('customers', $column)));

        if ($dropColumns === []) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) use ($dropColumns): void {
            $table->dropColumn($dropColumns);
        });
    }

    private function dropVendorColumns(): void
    {
        if (!Schema::hasTable('vendors')) {
            return;
        }

        $columns = [
            'fiscal_residency_status',
            'vendor_type',
            'fiscal_country',
            'vat_regime',
            'supply_type',
            'payment_currency_code',
            'foreign_tax_number',
            'withholding_tax_applicable',
            'reverse_charge_applicable',
            'adt_eligible',
            'adt_country',
            'compliance_documents',
            'fiscal_identity_locked_at',
            'fiscal_identity_lock_reason',
        ];

        $dropColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('vendors', $column)));

        if ($dropColumns === []) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) use ($dropColumns): void {
            $table->dropColumn($dropColumns);
        });
    }
};
