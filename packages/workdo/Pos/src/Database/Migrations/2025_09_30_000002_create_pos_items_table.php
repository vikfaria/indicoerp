<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_items')) {
            Schema::create('pos_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_id')->nullable()->index();
                $table->foreignId('product_id')->nullable()->index();
                $table->decimal('quantity', 8, 2);
                $table->decimal('price', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->json('tax_ids')->nullable();
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('pos_id')->references('id')->on('pos')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('product_service_items')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_items');
    }
};