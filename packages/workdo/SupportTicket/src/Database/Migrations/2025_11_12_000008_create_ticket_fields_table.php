<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_fields')) {
            Schema::create('ticket_fields', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('type')->default('text');
                $table->string('placeholder')->nullable();
                $table->string('width')->default(6);
                $table->integer('order')->default(0);
                $table->integer('status')->default(1);
                $table->boolean('is_required')->default('1');
                $table->integer('custom_id')->nullable();
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
        Schema::dropIfExists('ticket_fields');
    }
};