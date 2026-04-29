<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_transfers')) {
            Schema::create('employee_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('from_branch_id')->nullable();
                $table->foreignId('from_department_id')->nullable();
                $table->foreignId('from_designation_id')->nullable();
                $table->foreignId('to_branch_id')->nullable();
                $table->foreignId('to_department_id')->nullable();
                $table->foreignId('to_designation_id')->nullable();
                $table->date('transfer_date')->nullable();
                $table->date('effective_date');
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'approved', 'in progress', 'rejected', 'cancelled'])->default('pending');
                $table->string('document')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->foreignId('creator_id')->nullable();
                $table->foreignId('created_by');
                $table->timestamps();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('from_branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('from_department_id')->references('id')->on('departments')->onDelete('set null');
                $table->foreign('from_designation_id')->references('id')->on('designations')->onDelete('set null');
                $table->foreign('to_branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('to_department_id')->references('id')->on('departments')->onDelete('set null');
                $table->foreign('to_designation_id')->references('id')->on('designations')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_transfers');
    }
};
