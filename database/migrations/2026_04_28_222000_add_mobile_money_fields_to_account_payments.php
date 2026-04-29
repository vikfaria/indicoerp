<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_payments', 'payment_method')) {
                $table->string('payment_method', 30)->default('bank_transfer')->after('bank_account_id');
            }

            if (!Schema::hasColumn('customer_payments', 'mobile_money_provider')) {
                $table->string('mobile_money_provider', 20)->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('customer_payments', 'mobile_money_number')) {
                $table->string('mobile_money_number', 30)->nullable()->after('mobile_money_provider');
            }
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_payments', 'payment_method')) {
                $table->string('payment_method', 30)->default('bank_transfer')->after('bank_account_id');
            }

            if (!Schema::hasColumn('vendor_payments', 'mobile_money_provider')) {
                $table->string('mobile_money_provider', 20)->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('vendor_payments', 'mobile_money_number')) {
                $table->string('mobile_money_number', 30)->nullable()->after('mobile_money_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'mobile_money_number')) {
                $table->dropColumn('mobile_money_number');
            }

            if (Schema::hasColumn('customer_payments', 'mobile_money_provider')) {
                $table->dropColumn('mobile_money_provider');
            }

            if (Schema::hasColumn('customer_payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_payments', 'mobile_money_number')) {
                $table->dropColumn('mobile_money_number');
            }

            if (Schema::hasColumn('vendor_payments', 'mobile_money_provider')) {
                $table->dropColumn('mobile_money_provider');
            }

            if (Schema::hasColumn('vendor_payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
