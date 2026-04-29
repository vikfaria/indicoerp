<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('credit_note_items'))
        {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('credit_note_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_percentage', 5, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};