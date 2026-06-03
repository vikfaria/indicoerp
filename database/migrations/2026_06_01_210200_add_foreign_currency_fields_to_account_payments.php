<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('customer_payments', 'currency_code')) {
                $table->string('currency_code', 3)->default('MZN')->after('payment_amount');
            }

            if (!Schema::hasColumn('customer_payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency_code');
            }

            if (!Schema::hasColumn('customer_payments', 'foreign_amount')) {
                $table->decimal('foreign_amount', 15, 2)->nullable()->after('exchange_rate');
            }

            if (!Schema::hasColumn('customer_payments', 'amount_mzn')) {
                $table->decimal('amount_mzn', 15, 2)->nullable()->after('foreign_amount');
            }

            if (!Schema::hasColumn('customer_payments', 'fx_difference_amount')) {
                $table->decimal('fx_difference_amount', 15, 2)->default(0)->after('amount_mzn');
            }
        });

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payments', 'currency_code')) {
                $table->string('currency_code', 3)->default('MZN')->after('payment_amount');
            }

            if (!Schema::hasColumn('vendor_payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency_code');
            }

            if (!Schema::hasColumn('vendor_payments', 'foreign_amount')) {
                $table->decimal('foreign_amount', 15, 2)->nullable()->after('exchange_rate');
            }

            if (!Schema::hasColumn('vendor_payments', 'amount_mzn')) {
                $table->decimal('amount_mzn', 15, 2)->nullable()->after('foreign_amount');
            }

            if (!Schema::hasColumn('vendor_payments', 'fx_difference_amount')) {
                $table->decimal('fx_difference_amount', 15, 2)->default(0)->after('amount_mzn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_payments', 'fx_difference_amount')) {
                $table->dropColumn('fx_difference_amount');
            }

            if (Schema::hasColumn('customer_payments', 'amount_mzn')) {
                $table->dropColumn('amount_mzn');
            }

            if (Schema::hasColumn('customer_payments', 'foreign_amount')) {
                $table->dropColumn('foreign_amount');
            }

            if (Schema::hasColumn('customer_payments', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }

            if (Schema::hasColumn('customer_payments', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_payments', 'fx_difference_amount')) {
                $table->dropColumn('fx_difference_amount');
            }

            if (Schema::hasColumn('vendor_payments', 'amount_mzn')) {
                $table->dropColumn('amount_mzn');
            }

            if (Schema::hasColumn('vendor_payments', 'foreign_amount')) {
                $table->dropColumn('foreign_amount');
            }

            if (Schema::hasColumn('vendor_payments', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }

            if (Schema::hasColumn('vendor_payments', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};

