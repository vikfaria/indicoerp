<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('leads'))
        {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('subject');
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('pipeline_id')->nullable()->index();
                $table->foreignId('stage_id')->nullable()->index();
                $table->string('sources')->nullable();
                $table->string('products')->nullable();
                $table->longText('notes')->nullable();
                $table->string('labels')->nullable();
                $table->integer('order')->nullable();
                $table->string('phone', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('is_converted')->default(0);
                $table->date('date')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('stage_id')->references('id')->on('lead_stages')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};