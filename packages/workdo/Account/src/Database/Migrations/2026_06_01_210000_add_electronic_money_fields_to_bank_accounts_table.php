<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('bank_accounts', 'is_electronic_money_account')) {
                $table->boolean('is_electronic_money_account')
                    ->default(false)
                    ->after('is_active')
                    ->index();
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_entity')) {
                $table->string('electronic_money_entity', 120)
                    ->nullable()
                    ->after('is_electronic_money_account');
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_level')) {
                $table->string('electronic_money_level', 20)
                    ->nullable()
                    ->after('electronic_money_entity');
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_daily_limit_mzn')) {
                $table->decimal('electronic_money_daily_limit_mzn', 15, 2)
                    ->nullable()
                    ->after('electronic_money_level');
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_monthly_limit_mzn')) {
                $table->decimal('electronic_money_monthly_limit_mzn', 15, 2)
                    ->nullable()
                    ->after('electronic_money_daily_limit_mzn');
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_limit_exempt_for_enterprise')) {
                $table->boolean('electronic_money_limit_exempt_for_enterprise')
                    ->default(false)
                    ->after('electronic_money_monthly_limit_mzn');
            }

            if (!Schema::hasColumn('bank_accounts', 'electronic_money_account_purpose')) {
                $table->string('electronic_money_account_purpose', 255)
                    ->nullable()
                    ->after('electronic_money_limit_exempt_for_enterprise');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bank_accounts')) {
            return;
        }

        $columns = [
            'is_electronic_money_account',
            'electronic_money_entity',
            'electronic_money_level',
            'electronic_money_daily_limit_mzn',
            'electronic_money_monthly_limit_mzn',
            'electronic_money_limit_exempt_for_enterprise',
            'electronic_money_account_purpose',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('bank_accounts', $column)) {
                continue;
            }

            Schema::table('bank_accounts', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
