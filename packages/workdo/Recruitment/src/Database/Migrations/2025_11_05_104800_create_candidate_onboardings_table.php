<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_onboardings')) {
            Schema::create('candidate_onboardings', function (Blueprint $table) {
                $table->id();
                $table->date('start_date');
                $table->enum('status', ['Pending', 'In Progress', 'Completed'])->default('Pending');
                $table->foreignId('candidate_id')->index();
                $table->foreignId('checklist_id')->index();
                $table->foreignId('buddy_employee_id')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                $table->foreign('checklist_id')->references('id')->on('onboarding_checklists')->onDelete('cascade');
                $table->foreign('buddy_employee_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_onboardings');
    }
};
