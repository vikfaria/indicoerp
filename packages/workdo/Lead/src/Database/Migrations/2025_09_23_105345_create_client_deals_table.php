<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('client_deals'))
        {
            Schema::create('client_deals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->index();
                $table->foreignId('deal_id')->index();
                $table->timestamps();
                
                $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
                $table->unique(['client_id', 'deal_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_deals');
    }
};