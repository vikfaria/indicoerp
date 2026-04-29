<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos')) {
            Schema::create('pos', function (Blueprint $table) {
                $table->id();
                $table->string('sale_number');
                $table->foreignId('customer_id')->nullable()->index();
                $table->foreignId('warehouse_id')->nullable()->index();
                $table->date('pos_date');
                $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');
                $table->foreignId('bank_account_id')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();
               
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos');
    }
};