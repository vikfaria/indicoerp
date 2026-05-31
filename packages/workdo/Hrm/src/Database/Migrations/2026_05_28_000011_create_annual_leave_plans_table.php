<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('annual_leave_plans')) {
            return;
        }

        Schema::create('annual_leave_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->onDelete('set null');
            $table->unsignedSmallInteger('leave_year');
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->unsignedInteger('planned_days');
            $table->string('status', 24)->default('pending_manager')->index();
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('manager_approved_at')->nullable();
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hr_approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['created_by', 'employee_id', 'leave_year'], 'annual_leave_plans_company_employee_year_idx');
            $table->index(['created_by', 'status'], 'annual_leave_plans_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_leave_plans');
    }
};
