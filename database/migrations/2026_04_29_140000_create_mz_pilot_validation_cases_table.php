<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mz_pilot_validation_cases')) {
            return;
        }

        Schema::create('mz_pilot_validation_cases', function (Blueprint $table): void {
            $table->id();
            $table->enum('domain', ['payroll', 'accounting']);
            $table->string('company_name', 180);
            $table->string('company_nuit', 32)->nullable();
            $table->string('industry_sector', 120)->nullable();
            $table->string('scenario_code', 64)->nullable();
            $table->text('scenario_description')->nullable();
            $table->enum('result', ['pending', 'passed', 'failed'])->default('pending');
            $table->date('executed_at')->nullable();
            $table->string('evidence_ref', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mz_pilot_validation_cases');
    }
};
