<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('goals'))
        {
            Schema::create('goals', function (Blueprint $table) {
                $table->id();
                $table->string('goal_name');
                $table->longText('goal_description')->nullable();
                $table->foreignId('category_id')->constrained('goal_categories')->onDelete('cascade');
                $table->enum('goal_type', ['savings', 'debt_reduction', 'expense_reduction']);
                $table->decimal('target_amount', 15, 2);
                $table->decimal('current_amount', 15, 2)->default(0);
                $table->date('start_date');
                $table->date('target_date');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
                $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');

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
        Schema::dropIfExists('goals');
    }
};
