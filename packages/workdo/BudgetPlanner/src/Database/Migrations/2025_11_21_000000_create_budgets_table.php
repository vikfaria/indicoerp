<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('budgets'))
        {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->string('budget_name');
                $table->foreignId('period_id')->constrained('budget_periods')->onDelete('cascade');
                $table->enum('budget_type', ['operational', 'capital', 'cash_flow']);
                $table->decimal('total_budget_amount', 15, 2);
                $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
                $table->foreignId('approved_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
