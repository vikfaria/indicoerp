<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_foreign_worker_profiles')) {
            Schema::create('employee_foreign_worker_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->unique()->constrained('employees')->onDelete('cascade');
                $table->boolean('is_foreign_worker')->default(false)->index();
                $table->string('nationality', 120)->nullable();
                $table->string('residency_status', 30)->default('resident');
                $table->string('passport_number', 120)->nullable();
                $table->date('passport_expires_at')->nullable();
                $table->string('visa_type', 80)->nullable();
                $table->date('visa_expires_at')->nullable();
                $table->string('work_authorization_number', 120)->nullable();
                $table->date('work_authorization_expires_at')->nullable();
                $table->string('hiring_regime', 50)->nullable();
                $table->string('work_province', 120)->nullable();
                $table->date('mozambique_entry_date')->nullable();
                $table->date('cessation_notification_due_at')->nullable();
                $table->date('cessation_notified_at')->nullable();
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
        Schema::dropIfExists('employee_foreign_worker_profiles');
    }
};
