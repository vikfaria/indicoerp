<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('vendor_payment_allocations'))
        {
            Schema::create('vendor_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('allocated_amount', 15, 2);
                $table->timestamps();

                $table->foreign('payment_id')->references('id')->on('vendor_payments')->onDelete('cascade');
                $table->foreign('invoice_id')->references('id')->on('purchase_invoices');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vendor_payment_allocations');
    }
};
