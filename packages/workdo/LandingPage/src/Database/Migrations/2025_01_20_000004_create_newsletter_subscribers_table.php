<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('newsletter_subscribers'))
        {
            Schema::create('newsletter_subscribers', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->timestamp('subscribed_at');
                $table->string('ip_address')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->string('region')->nullable();
                $table->string('country_code', 2)->nullable();
                $table->string('isp')->nullable();
                $table->string('org')->nullable();
                $table->string('timezone')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('user_agent')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->string('device')->nullable();
                $table->timestamps();
                
                $table->index('subscribed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};