<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->enum('payroll_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
                $table->date('pay_period_start')->nullable();
                $table->date('pay_period_end')->nullable();
                $table->date('pay_date')->nullable();
                $table->longText('notes')->nullable();
                $table->decimal('total_gross_pay', 10, 2)->nullable();
                $table->decimal('total_deductions', 10, 2)->nullable();
                $table->decimal('total_net_pay', 10, 2)->nullable();
                $table->integer('employee_count')->nullable();
                $table->enum('status', ['draft', 'processing', 'completed', 'cancelled'])->default('draft');
                $table->enum('is_payroll_paid', ['paid', 'unpaid'])->default('unpaid');
                $table->foreignId('bank_account_id')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
