<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('previous_branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('previous_department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->foreignId('previous_designation_id')->nullable()->constrained('designations')->onDelete('set null');
                $table->foreignId('current_branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('current_department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->foreignId('current_designation_id')->nullable()->constrained('designations')->onDelete('set null');
                $table->string('effective_date')->nullable();
                $table->string('reason')->nullable();
                $table->string('document')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
