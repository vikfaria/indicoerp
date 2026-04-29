<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mz_tax_account_mappings')) {
            Schema::create('mz_tax_account_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vat_output_account_id')->nullable();
                $table->foreignId('vat_input_account_id')->nullable();
                $table->foreignId('withholding_payable_account_id')->nullable();
                $table->foreignId('withholding_receivable_account_id')->nullable();
                $table->foreignId('irpc_expense_account_id')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('creator_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Keep MySQL/MariaDB-compatible foreign key names (<64 chars).
                $table->foreign('vat_output_account_id', 'mz_tam_vat_out_fk')->references('id')->on('chart_of_accounts')->nullOnDelete();
                $table->foreign('vat_input_account_id', 'mz_tam_vat_in_fk')->references('id')->on('chart_of_accounts')->nullOnDelete();
                $table->foreign('withholding_payable_account_id', 'mz_tam_wh_pay_fk')->references('id')->on('chart_of_accounts')->nullOnDelete();
                $table->foreign('withholding_receivable_account_id', 'mz_tam_wh_rec_fk')->references('id')->on('chart_of_accounts')->nullOnDelete();
                $table->foreign('irpc_expense_account_id', 'mz_tam_irpc_exp_fk')->references('id')->on('chart_of_accounts')->nullOnDelete();

                $table->index(['created_by', 'is_active', 'effective_from'], 'mz_tax_mapping_company_active_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mz_tax_account_mappings');
    }
};
