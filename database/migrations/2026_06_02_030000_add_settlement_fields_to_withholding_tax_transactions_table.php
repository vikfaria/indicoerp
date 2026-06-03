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
            if (!Schema::hasColumn('withholding_tax_transactions', 'declaration_reference')) {
                $table->string('declaration_reference', 120)->nullable()->after('status');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'declared_at')) {
                $table->dateTime('declared_at')->nullable()->after('declaration_reference');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'declared_by')) {
                $table->unsignedBigInteger('declared_by')->nullable()->after('declared_at');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'state_payment_reference')) {
                $table->string('state_payment_reference', 120)->nullable()->after('declared_by');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('state_payment_reference');
            }

            if (!Schema::hasColumn('withholding_tax_transactions', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            return;
        }

        Schema::table('withholding_tax_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('withholding_tax_transactions', 'paid_by')) {
                $table->dropColumn('paid_by');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'state_payment_reference')) {
                $table->dropColumn('state_payment_reference');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'declared_by')) {
                $table->dropColumn('declared_by');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'declared_at')) {
                $table->dropColumn('declared_at');
            }

            if (Schema::hasColumn('withholding_tax_transactions', 'declaration_reference')) {
                $table->dropColumn('declaration_reference');
            }
        });
    }
};
