<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_invoice_items', 'vat_code')) {
                    $table->string('vat_code', 20)->nullable()->after('tax_percentage');
                }

                if (!Schema::hasColumn('purchase_invoice_items', 'tax_exemption_reason')) {
                    $table->string('tax_exemption_reason', 255)->nullable()->after('vat_code');
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_item_taxes')) {
            Schema::table('purchase_invoice_item_taxes', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_invoice_item_taxes', 'vat_code')) {
                    $table->string('vat_code', 20)->nullable()->after('tax_rate');
                }

                if (!Schema::hasColumn('purchase_invoice_item_taxes', 'tax_exemption_reason')) {
                    $table->string('tax_exemption_reason', 255)->nullable()->after('vat_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_invoice_item_taxes')) {
            Schema::table('purchase_invoice_item_taxes', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_invoice_item_taxes', 'tax_exemption_reason')) {
                    $table->dropColumn('tax_exemption_reason');
                }

                if (Schema::hasColumn('purchase_invoice_item_taxes', 'vat_code')) {
                    $table->dropColumn('vat_code');
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_invoice_items', 'tax_exemption_reason')) {
                    $table->dropColumn('tax_exemption_reason');
                }

                if (Schema::hasColumn('purchase_invoice_items', 'vat_code')) {
                    $table->dropColumn('vat_code');
                }
            });
        }
    }
};
