<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos')) {
            return;
        }

        Schema::table('pos', function (Blueprint $table): void {
            if (!Schema::hasColumn('pos', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('bank_account_id');
            }

            if (!Schema::hasColumn('pos', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pos')) {
            return;
        }

        Schema::table('pos', function (Blueprint $table): void {
            if (Schema::hasColumn('pos', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }

            if (Schema::hasColumn('pos', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};

