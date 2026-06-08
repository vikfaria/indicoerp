<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('onboarding_sessions')) {
            Schema::create('onboarding_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->string('status', 20)->default('active');
                $table->string('current_module_key', 120)->nullable();
                $table->string('current_step_key', 120)->nullable();
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('abandoned_at')->nullable();
                $table->text('completion_note')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'completed_at']);
                $table->index(['company_id', 'current_module_key']);
                $table->index(['company_id', 'current_step_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_sessions');
    }
};
