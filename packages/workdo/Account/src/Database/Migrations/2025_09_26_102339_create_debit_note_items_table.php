<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('debit_note_items'))
        {
            Schema::create('debit_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('debit_note_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('quantity', 15, 2);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_percentage', 5, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('debit_note_id')->references('id')->on('debit_notes')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_items');
    }
};