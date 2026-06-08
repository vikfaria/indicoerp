<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('onboarding_checklist_items')) {
            Schema::create('onboarding_checklist_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('onboarding_session_id')->index();
                $table->foreignId('onboarding_step_id')->index();
                $table->foreignId('company_id')->index();
                $table->string('item_key', 160);
                $table->string('item_label', 190);
                $table->text('item_description')->nullable();
                $table->unsignedSmallInteger('item_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->string('state', 20)->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('skipped_at')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->text('skip_reason')->nullable();
                $table->json('evidence')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('onboarding_session_id')->references('id')->on('onboarding_sessions')->onDelete('cascade');
                $table->foreign('onboarding_step_id')->references('id')->on('onboarding_steps')->onDelete('cascade');
                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['onboarding_step_id', 'item_key']);
                $table->index(['company_id', 'state']);
                $table->index(['company_id', 'onboarding_step_id']);
                $table->index(['onboarding_session_id', 'state']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_checklist_items');
    }
};
