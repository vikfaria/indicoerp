<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_feedbacks')) {
            Schema::create('interview_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->integer('technical_rating')->nullable();
                $table->integer('communication_rating')->nullable();
                $table->integer('cultural_fit_rating')->nullable();
                $table->integer('overall_rating')->nullable();
                $table->longText('strengths')->nullable();
                $table->longText('weaknesses')->nullable();
                $table->longText('comments')->nullable();
                $table->string('recommendation')->default('0');
                $table->foreignId('interview_id')->nullable()->index();
                $table->json('interviewer_ids')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('interview_id')->references('id')->on('interviews')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedbacks');
    }
};
