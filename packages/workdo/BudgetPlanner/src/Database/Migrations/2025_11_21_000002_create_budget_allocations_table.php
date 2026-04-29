<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('budget_allocations'))
        {
            Schema::create('budget_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
                $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
                $table->decimal('allocated_amount', 15, 2);
                $table->decimal('spent_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};