<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('lead_calls'))
        {
            Schema::create('lead_calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->index();
                $table->string('subject');
                $table->string('call_type');
                $table->string('duration');
                $table->foreignId('user_id')->index();
                $table->longText('description')->nullable();
                $table->longText('call_result')->nullable();
                $table->timestamps();
                
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_calls');
    }
};