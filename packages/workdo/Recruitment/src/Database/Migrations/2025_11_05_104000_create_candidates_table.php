<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidates')) {
            Schema::create('candidates', function (Blueprint $table) {
                $table->id();
                $table->string('tracking_id')->unique()->nullable();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('gender')->nullable();
                $table->date('dob')->nullable();
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('current_company')->nullable();
                $table->string('current_position')->nullable();
                $table->decimal('experience_years', 10, 2)->nullable();
                $table->decimal('current_salary', 10, 2)->nullable();
                $table->decimal('expected_salary', 10, 2)->nullable();
                $table->string('notice_period')->nullable();
                $table->longText('skills')->nullable();
                $table->longText('education')->nullable();
                $table->string('portfolio_url')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('profile_path')->nullable();
                $table->string('resume_path')->nullable();
                $table->string('cover_letter_path')->nullable();
                $table->string('status')->default('0');
                $table->date('application_date')->nullable();
                $table->longText('custom_question')->nullable();

                $table->foreignId('job_id')->nullable()->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('source_id')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('source_id')->references('id')->on('candidate_sources')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
