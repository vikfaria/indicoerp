<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('user_leads'))
        {
            Schema::create('user_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->foreignId('lead_id')->index();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
                $table->unique(['user_id', 'lead_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_leads');
    }
};