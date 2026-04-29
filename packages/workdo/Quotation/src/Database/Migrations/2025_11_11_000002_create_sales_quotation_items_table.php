<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('sales_quotation_items'))
        {
            Schema::create('sales_quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->index();
                $table->foreignId('product_id')->index();
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_percentage', 5, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);

                $table->foreign('quotation_id')->references('id')->on('sales_quotations')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_items');
    }
};