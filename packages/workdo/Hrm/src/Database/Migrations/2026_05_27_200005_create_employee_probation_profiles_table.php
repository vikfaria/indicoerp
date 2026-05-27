<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_probation_profiles')) {
            Schema::create('employee_probation_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->unique()->constrained('employees')->onDelete('cascade');
                $table->string('probation_category', 40)->default('general');
                $table->date('starts_at');
                $table->date('expected_end_at');
                $table->unsignedSmallInteger('legal_max_days');
                $table->string('evaluation_status', 30)->default('pending');
                $table->unsignedTinyInteger('technical_score')->nullable();
                $table->unsignedTinyInteger('attendance_score')->nullable();
                $table->unsignedTinyInteger('punctuality_score')->nullable();
                $table->unsignedTinyInteger('conduct_score')->nullable();
                $table->unsignedTinyInteger('adaptation_score')->nullable();
                $table->string('recommendation', 30)->nullable();
                $table->string('decision_status', 30)->default('ongoing');
                $table->date('decision_date')->nullable();
                $table->text('cessation_reason')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_probation_profiles');
    }
};
