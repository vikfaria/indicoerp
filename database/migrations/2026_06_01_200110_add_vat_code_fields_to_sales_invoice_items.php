<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table): void {
                if (!Schema::hasColumn('sales_invoice_items', 'vat_code')) {
                    $table->string('vat_code', 20)->nullable()->after('tax_percentage')->index('sales_invoice_items_vat_code_idx');
                }

                if (!Schema::hasColumn('sales_invoice_items', 'tax_exemption_reason')) {
                    $table->string('tax_exemption_reason', 255)->nullable()->after('vat_code');
                }
            });
        }

        if (Schema::hasTable('sales_invoice_item_taxes')) {
            Schema::table('sales_invoice_item_taxes', function (Blueprint $table): void {
                if (!Schema::hasColumn('sales_invoice_item_taxes', 'vat_code')) {
                    $table->string('vat_code', 20)->nullable()->after('tax_rate');
                }

                if (!Schema::hasColumn('sales_invoice_item_taxes', 'tax_exemption_reason')) {
                    $table->string('tax_exemption_reason', 255)->nullable()->after('vat_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoice_items')) {
            $itemColumns = [];
            if (Schema::hasColumn('sales_invoice_items', 'vat_code')) {
                $itemColumns[] = 'vat_code';
            }
            if (Schema::hasColumn('sales_invoice_items', 'tax_exemption_reason')) {
                $itemColumns[] = 'tax_exemption_reason';
            }

            if ($itemColumns !== []) {
                Schema::table('sales_invoice_items', function (Blueprint $table) use ($itemColumns): void {
                    $table->dropColumn($itemColumns);
                });
            }
        }

        if (Schema::hasTable('sales_invoice_item_taxes')) {
            $taxColumns = [];
            if (Schema::hasColumn('sales_invoice_item_taxes', 'vat_code')) {
                $taxColumns[] = 'vat_code';
            }
            if (Schema::hasColumn('sales_invoice_item_taxes', 'tax_exemption_reason')) {
                $taxColumns[] = 'tax_exemption_reason';
            }

            if ($taxColumns !== []) {
                Schema::table('sales_invoice_item_taxes', function (Blueprint $table) use ($taxColumns): void {
                    $table->dropColumn($taxColumns);
                });
            }
        }
    }
};
