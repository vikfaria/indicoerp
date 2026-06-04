<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_accounts') && !Schema::hasColumn('bank_accounts', 'branch_id')) {
            Schema::table('bank_accounts', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('branch_name')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        foreach (['vendor_payments', 'customer_payments'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->foreignId('branch_id')
                        ->nullable()
                        ->after('bank_account_id')
                        ->constrained('branches')
                        ->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('bank_accounts')) {
            $bankAccounts = DB::table('bank_accounts')
                ->select('id', 'branch_name', 'created_by')
                ->whereNull('branch_id')
                ->whereNotNull('branch_name')
                ->get();

            foreach ($bankAccounts as $bankAccount) {
                $branchName = trim((string) $bankAccount->branch_name);
                $companyId = (int) ($bankAccount->created_by ?? 0);

                if ($branchName === '' || $companyId <= 0) {
                    continue;
                }

                $branch = DB::table('branches')
                    ->where('created_by', $companyId)
                    ->whereRaw('LOWER(TRIM(branch_name)) = ?', [strtolower($branchName)])
                    ->first();

                if (!$branch) {
                    continue;
                }

                DB::table('bank_accounts')
                    ->where('id', $bankAccount->id)
                    ->update([
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->branch_name,
                    ]);
            }
        }

        foreach (['vendor_payments', 'customer_payments'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $payments = DB::table($tableName)
                ->select('id', 'bank_account_id')
                ->whereNull('branch_id')
                ->get();

            foreach ($payments as $payment) {
                $branchId = DB::table('bank_accounts')
                    ->where('id', $payment->bank_account_id)
                    ->value('branch_id');

                if (!$branchId) {
                    continue;
                }

                DB::table($tableName)
                    ->where('id', $payment->id)
                    ->update([
                        'branch_id' => $branchId,
                    ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['vendor_payments', 'customer_payments'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('branch_id');
                });
            }
        }

        if (Schema::hasTable('bank_accounts') && Schema::hasColumn('bank_accounts', 'branch_id')) {
            Schema::table('bank_accounts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
