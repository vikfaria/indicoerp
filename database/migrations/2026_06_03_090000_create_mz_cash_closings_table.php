<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mz_cash_closings')) {
            Schema::create('mz_cash_closings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
                $table->date('closing_date');
                $table->enum('status', ['closed', 'reopened'])->default('closed');
                $table->decimal('opening_balance_mzn', 15, 2)->default(0);
                $table->decimal('cash_in_mzn', 15, 2)->default(0);
                $table->decimal('cash_out_mzn', 15, 2)->default(0);
                $table->decimal('expected_balance_mzn', 15, 2)->default(0);
                $table->decimal('counted_balance_mzn', 15, 2)->default(0);
                $table->decimal('variance_mzn', 15, 2)->default(0);
                $table->text('close_reason')->nullable();
                $table->text('reopen_reason')->nullable();
                $table->json('snapshot')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('reopened_at')->nullable();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['created_by', 'bank_account_id', 'closing_date'], 'mz_cash_closing_company_account_date_idx');
                $table->index(['created_by', 'status', 'closing_date'], 'mz_cash_closing_company_status_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mz_cash_closings');
    }
};
