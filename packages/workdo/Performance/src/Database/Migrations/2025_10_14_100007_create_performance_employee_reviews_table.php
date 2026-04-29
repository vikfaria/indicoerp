<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('performance_employee_reviews')) {
            Schema::create('performance_employee_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('reviewer_id')->nullable()->index();
                $table->foreignId('review_cycle_id')->nullable()->index();
                $table->date('review_date');
                $table->date('completion_date')->nullable();
                $table->string('rating')->nullable();
                $table->text('pros')->nullable();
                $table->text('cons')->nullable();
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('review_cycle_id')->references('id')->on('performance_review_cycles')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_employee_reviews');
    }
};