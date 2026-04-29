<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mz_fiscal_closings')) {
            Schema::create('mz_fiscal_closings', function (Blueprint $table) {
                $table->id();
                $table->date('period_from');
                $table->date('period_to');
                $table->enum('status', ['closed', 'reopened'])->default('closed');
                $table->text('close_reason')->nullable();
                $table->text('reopen_reason')->nullable();
                $table->json('snapshot')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->unsignedBigInteger('reopened_by')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('reopened_at')->nullable();
                $table->unsignedBigInteger('creator_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['created_by', 'status', 'period_from', 'period_to'], 'mz_fiscal_closing_company_period_idx');
                $table->index(['created_by', 'closed_at'], 'mz_fiscal_closing_company_closed_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mz_fiscal_closings');
    }
};

