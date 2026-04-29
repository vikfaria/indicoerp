<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('deals'))
        {
            Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->foreignId('pipeline_id')->index();
            $table->foreignId('stage_id')->index();
            $table->json('sources')->nullable();
            $table->json('products')->nullable();
            $table->longText('notes')->nullable();
            $table->json('labels')->nullable();
            $table->string('status')->default('0');
            $table->integer('order')->nullable()->default(0);
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(false);                
            $table->foreignId('creator_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            
            $table->foreign('stage_id')->references('id')->on('deal_stages')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('cascade');
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};