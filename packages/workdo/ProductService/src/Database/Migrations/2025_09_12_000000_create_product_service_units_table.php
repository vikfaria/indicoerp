<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_service_units')) {
            Schema::create('product_service_units', function (Blueprint $table) {
                $table->id();
                $table->string('unit_name');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id', 'product_service_units_creator_id_foreign')->references('id')->on('users');
                $table->foreign('created_by', 'product_service_units_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_service_units');
    }
};
