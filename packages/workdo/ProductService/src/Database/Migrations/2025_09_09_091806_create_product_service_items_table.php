<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_service_items')) {
            Schema::create('product_service_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->json('tax_ids')->nullable();
                $table->foreignId('category_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->longtext('long_description')->nullable();
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->decimal('purchase_price', 10, 2)->nullable();
                $table->string('unit')->nullable();
                $table->string('image')->nullable();
                $table->json('images')->nullable();
                $table->string('type')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id', 'product_service_items_creator_id_foreign')->references('id')->on('users');
                $table->foreign('created_by', 'product_service_items_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('category_id', 'product_service_items_category_id_foreign')->references('id')->on('product_service_categories')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_service_items');
    }
};
