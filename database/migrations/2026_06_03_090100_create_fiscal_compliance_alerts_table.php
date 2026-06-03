<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiscal_compliance_alerts')) {
            Schema::create('fiscal_compliance_alerts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->string('alert_key', 120);
                $table->string('label', 190);
                $table->string('severity', 20)->default('medium');
                $table->unsignedInteger('count')->default(0);
                $table->string('status', 20)->default('open');
                $table->unsignedInteger('times_triggered')->default(0);
                $table->timestamp('first_detected_at')->nullable();
                $table->timestamp('last_detected_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('last_snapshot_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'alert_key']);
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'severity']);
                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_compliance_alerts');
    }
};
