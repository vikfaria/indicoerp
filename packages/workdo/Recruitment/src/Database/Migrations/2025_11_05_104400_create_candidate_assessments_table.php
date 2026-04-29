<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_assessments')) {
            Schema::create('candidate_assessments', function (Blueprint $table) {
                $table->id();
                $table->string('assessment_name');
                $table->integer('score')->nullable();
                $table->integer('max_score')->nullable();
                $table->string('pass_fail_status')->default('0');
                $table->longText('comments')->nullable();
                $table->string('assessment_date');

                $table->foreignId('candidate_id')->nullable()->index();
                $table->foreignId('conducted_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('set null');
                $table->foreign('conducted_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_assessments');
    }
};
