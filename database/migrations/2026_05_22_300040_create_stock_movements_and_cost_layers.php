<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('product_id');
                $table->enum('movement_type', ['purchase', 'sale', 'return_in', 'return_out', 'adjustment', 'transfer', 'production', 'consumption']);
                $table->date('movement_date');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->decimal('running_quantity', 15, 4)->default(0);
                $table->decimal('running_value', 15, 2)->default(0);
                $table->string('reference_type', 50)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('warehouse_code', 20)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['company_id', 'product_id', 'movement_date']);
            });
        }

        if (!Schema::hasTable('stock_cost_layers')) {
            Schema::create('stock_cost_layers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('stock_movement_id');
                $table->decimal('original_quantity', 15, 4);
                $table->decimal('remaining_quantity', 15, 4);
                $table->decimal('unit_cost', 15, 4);
                $table->date('entry_date');
                $table->boolean('is_exhausted')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->onDelete('cascade');
                $table->index(['company_id', 'product_id', 'is_exhausted']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_cost_layers');
        Schema::dropIfExists('stock_movements');
    }
};
