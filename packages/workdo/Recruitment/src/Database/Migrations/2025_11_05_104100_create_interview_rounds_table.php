<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_rounds')) {
            Schema::create('interview_rounds', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('sequence_number')->nullable();
                $table->longText('description')->nullable();
                $table->string('status')->default('0'); // 0 active, 1 - inactive

                $table->foreignId('job_id')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_rounds');
    }
};
