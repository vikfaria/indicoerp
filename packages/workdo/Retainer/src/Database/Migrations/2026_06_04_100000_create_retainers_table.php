<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retainers', function (Blueprint $table): void {
            $table->id();
            $table->string('retainer_number', 50)->unique();
            $table->date('retainer_date');
            $table->date('due_date')->nullable();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'converted', 'paid', 'partial'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['customer_id', 'status']);
            $table->index(['created_by', 'retainer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retainers');
    }
};
