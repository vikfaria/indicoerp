<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('offers')) {
            Schema::create('offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->index();
                $table->foreignId('job_id')->nullable()->index();
                $table->date('offer_date');
                $table->string('position');
                $table->foreignId('department_id')->nullable()->index();
                $table->decimal('salary', 10, 2);
                $table->decimal('bonus', 10, 2)->nullable();
                $table->string('equity')->nullable();
                $table->longText('benefits')->nullable();
                $table->date('start_date');
                $table->date('expiration_date');
                $table->string('offer_letter_path')->nullable();
                $table->string('status')->default('0');
                $table->date('response_date')->nullable();
                $table->longText('decline_reason')->nullable();
                $table->boolean('converted_to_employee')->default(false);
                $table->foreignId('employee_id')->nullable()->index();
                $table->foreignId('approved_by')->nullable()->index();
                $table->string('approval_status')->default('pending');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('set null');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
