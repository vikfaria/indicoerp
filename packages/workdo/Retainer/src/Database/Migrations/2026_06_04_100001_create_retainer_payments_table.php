<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retainer_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->date('payment_date');
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('payment_amount', 15, 2);
            $table->enum('status', ['pending', 'cleared', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['customer_id', 'status']);
            $table->index(['bank_account_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retainer_payments');
    }
};
