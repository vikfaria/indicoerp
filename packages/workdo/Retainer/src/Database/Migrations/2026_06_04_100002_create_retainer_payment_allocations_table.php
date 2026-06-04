<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retainer_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('retainer_payments')->cascadeOnDelete();
            $table->foreignId('retainer_id')->constrained('retainers')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['retainer_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retainer_payment_allocations');
    }
};
