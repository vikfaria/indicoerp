<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mz_pilot_companies')) {
            Schema::create('mz_pilot_companies', function (Blueprint $table): void {
                $table->id();
                $table->string('company_name', 180);
                $table->string('industry_sector', 120)->nullable();
                $table->string('contact_name', 180)->nullable();
                $table->string('contact_email', 180)->nullable();
                $table->string('contact_phone', 60)->nullable();
                $table->enum('status', ['planned', 'active', 'completed', 'on_hold', 'cancelled'])->default('planned');
                $table->date('pilot_start_date')->nullable();
                $table->date('pilot_end_date')->nullable();
                $table->text('validation_scope')->nullable();
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
        Schema::dropIfExists('mz_pilot_companies');
    }
};
