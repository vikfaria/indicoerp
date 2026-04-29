<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('employee_id');
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->default('Male');
                $table->foreignId('shift')->nullable()->constrained('shifts')->onDelete('set null');
                $table->string('attendance_policy')->nullable();
                $table->date('date_of_joining')->nullable();
                $table->string('employment_type')->default('0');
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_relationship')->nullable();
                $table->string('emergency_contact_number', 20)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_holder_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('bank_identifier_code')->nullable();
                $table->string('bank_branch')->nullable();
                $table->string('tax_payer_id')->nullable();
                $table->decimal('basic_salary', 10, 2)->nullable();
                $table->decimal('hours_per_day', 8, 2)->nullable();
                $table->decimal('days_per_week', 8, 2)->nullable();
                $table->decimal('rate_per_hour', 8, 2)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('set null');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
