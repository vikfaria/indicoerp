<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_social_security_profiles')) {
            Schema::create('employee_social_security_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->unique()->constrained('employees')->onDelete('cascade');
                $table->string('inss_number', 80)->nullable()->index();
                $table->date('registration_date')->nullable();
                $table->string('registration_status', 30)->default('pending');
                $table->string('identification_document_type', 80)->nullable();
                $table->string('identification_document_number', 120)->nullable();
                $table->string('evidence_file_path')->nullable();
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
        Schema::dropIfExists('employee_social_security_profiles');
    }
};
