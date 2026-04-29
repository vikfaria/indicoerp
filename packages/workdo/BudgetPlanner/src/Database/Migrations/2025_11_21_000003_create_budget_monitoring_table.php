<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('budget_monitorings'))
        {
            Schema::create('budget_monitorings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
                $table->date('monitoring_date');
                $table->decimal('total_allocated', 15, 2);
                $table->decimal('total_spent', 15, 2);
                $table->decimal('total_remaining', 15, 2);
                $table->decimal('variance_amount', 15, 2);
                $table->decimal('variance_percentage', 5, 2);
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
        Schema::dropIfExists('budget_monitorings');
    }
};
