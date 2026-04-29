<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->string('scheduled_date');
                $table->string('scheduled_time');
                $table->integer('duration')->nullable();
                $table->string('location')->nullable();
                $table->string('meeting_link')->nullable();
                $table->json('interviewer_ids')->nullable();
                $table->string('status')->default('0');
                $table->boolean('feedback_submitted')->default(false);

                $table->foreignId('candidate_id')->nullable()->index();
                $table->foreignId('job_id')->nullable()->index();
                $table->foreignId('round_id')->nullable()->index();
                $table->foreignId('interview_type_id')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('set null');
                $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('set null');
                $table->foreign('round_id')->references('id')->on('interview_rounds')->onDelete('set null');
                $table->foreign('interview_type_id')->references('id')->on('interview_types')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
