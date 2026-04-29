<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leave_applications')) {
            Schema::create('leave_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('leave_type_id')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('total_days')->nullable();
                $table->longText('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('attachment')->nullable();
                $table->longText('approver_comment')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade'); 
                $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
