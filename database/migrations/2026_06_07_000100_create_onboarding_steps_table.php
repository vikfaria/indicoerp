<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('onboarding_steps')) {
            Schema::create('onboarding_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('onboarding_session_id')->index();
                $table->foreignId('company_id')->index();
                $table->string('module_key', 120);
                $table->string('step_key', 160);
                $table->string('step_label', 190)->nullable();
                $table->unsignedSmallInteger('step_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->string('state', 20)->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('skipped_at')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->text('skip_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('onboarding_session_id')->references('id')->on('onboarding_sessions')->onDelete('cascade');
                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['onboarding_session_id', 'step_key']);
                $table->index(['company_id', 'state']);
                $table->index(['company_id', 'module_key']);
                $table->index(['company_id', 'step_order']);
                $table->index(['onboarding_session_id', 'state']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
    }
};
