<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_service_taxes')) {
            Schema::create('product_service_taxes', function (Blueprint $table) {
                $table->id();
                $table->string('tax_name');
                $table->decimal('rate', 5, 2);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id', 'product_service_taxes_creator_id_foreign')->references('id')->on('users');
                $table->foreign('created_by', 'product_service_taxes_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_service_taxes');
    }
};
