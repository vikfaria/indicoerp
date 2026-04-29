<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('budget_periods'))
        {
            Schema::create('budget_periods', function (Blueprint $table) {
                $table->id();
                $table->string('period_name');
                $table->string('financial_year');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('budget_periods');
    }
};
