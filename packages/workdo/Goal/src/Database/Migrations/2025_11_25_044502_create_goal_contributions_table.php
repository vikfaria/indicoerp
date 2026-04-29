<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('goal_contributions'))
        {
            Schema::create('goal_contributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_id')->constrained('goals')->onDelete('cascade');
                $table->date('contribution_date');
                $table->decimal('contribution_amount', 15, 2);
                $table->enum('contribution_type', ['manual', 'automatic'])->default('manual');
                $table->enum('reference_type', ['journal_entry', 'bank_transaction', 'manual'])->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->longText('notes')->nullable();
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
        Schema::dropIfExists('goal_contributions');
    }
};
