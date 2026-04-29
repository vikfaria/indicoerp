<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_entries')) {
            Schema::create('payroll_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
                $table->foreignId('employee_id');

                // Monetary values
                $table->decimal('basic_salary', 10, 2)->default(0);
                $table->decimal('total_allowances', 10, 2)->default(0);


                $table->decimal('total_deductions', 10, 2)->default(0);
                $table->decimal('total_loans', 10, 2)->default(0);
                $table->decimal('gross_pay', 10, 2)->default(0);
                $table->decimal('net_pay', 10, 2)->default(0);
                $table->decimal('per_day_salary', 10, 2)->default(0);

                // Days
                $table->integer('working_days')->default(0);
                $table->decimal('present_days', 5, 2)->default(0);
                $table->decimal('half_days', 5, 2)->default(0);
                $table->decimal('half_day_deduction', 10, 2)->default(0);
                $table->decimal('absent_days', 5, 2)->default(0);
                $table->decimal('absent_day_deduction', 10, 2)->default(0);
                $table->decimal('paid_leave_days', 5, 2)->default(0);
                $table->decimal('unpaid_leave_days', 5, 2)->default(0);
                $table->decimal('unpaid_leave_deduction', 10, 2)->default(0);

                // OverTime 
                $table->decimal('manual_overtime_hours', 5, 2)->default(0);
                $table->decimal('total_manual_overtimes', 10, 2)->default(0);
                $table->decimal('attendance_overtime_hours', 5, 2)->default(0);
                $table->decimal('attendance_overtime_rate', 10, 2)->default(0);
                $table->decimal('attendance_overtime_amount', 10, 2)->default(0);
                $table->decimal('overtime_hours', 5, 2)->default(0);

                // Status
                $table->enum('status', ['paid', 'unpaid'])->default('unpaid');

                // Breakdown JSONs
                $table->json('allowances_breakdown')->nullable();
                $table->json('deductions_breakdown')->nullable();
                $table->json('manual_overtimes_breakdown')->nullable();
                $table->json('loans_breakdown')->nullable();

                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->timestamps();

                $table->unique(['payroll_id', 'employee_id']);

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
