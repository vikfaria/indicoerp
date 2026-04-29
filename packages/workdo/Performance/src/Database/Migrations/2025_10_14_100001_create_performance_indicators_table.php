<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('performance_indicators')) {
            Schema::create('performance_indicators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('measurement_unit')->nullable();
                $table->string('target_value', 50)->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('category_id')->references('id')->on('performance_indicator_categories')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_indicators');
    }
};