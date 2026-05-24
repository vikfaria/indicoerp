<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recurring_journal_templates')) {
            Schema::create('recurring_journal_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('accounting_journal_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('frequency', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('next_run_date');
                $table->date('last_run_date')->nullable();
                $table->boolean('requires_approval')->default(true);
                $table->boolean('is_active')->default(true);
                $table->json('template_items')->comment('Array of {account_id, description, debit, credit}');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->unsignedInteger('executions_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['is_active', 'next_run_date']);
            });
        }

        if (!Schema::hasTable('monthly_closing_checklists')) {
            Schema::create('monthly_closing_checklists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('accounting_period_id');
                $table->unsignedBigInteger('company_id');
                $table->string('check_key', 50)->comment('vat, bank_reconciliation, ar, ap, stock, payroll, depreciation, withholdings');
                $table->string('check_name');
                $table->enum('status', ['pending', 'completed', 'skipped', 'not_applicable'])->default('pending');
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('cascade');
                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['accounting_period_id', 'check_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closing_checklists');
        Schema::dropIfExists('recurring_journal_templates');
    }
};
