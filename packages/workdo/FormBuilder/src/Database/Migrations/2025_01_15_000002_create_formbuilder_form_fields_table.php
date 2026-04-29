<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('form_fields')) {
            Schema::create('form_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_id')->constrained('forms')->onDelete('cascade');
                $table->string('label');
                $table->enum('type', ['text', 'email', 'number', 'tel', 'url', 'password', 'textarea', 'select', 'radio', 'checkbox', 'date', 'time']);
                $table->boolean('required')->default(false);
                $table->string('placeholder')->nullable();
                $table->json('options')->nullable();
                $table->integer('order')->default(0);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();
                
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('form_fields');
    }
};