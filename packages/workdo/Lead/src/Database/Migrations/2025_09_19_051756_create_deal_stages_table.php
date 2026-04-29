<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('deal_stages'))
        {
            Schema::create('deal_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('order')->nullable();
                $table->foreignId('pipeline_id')->nullable()->constrained('pipelines')->onDelete('set null');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_stages');
    }
};