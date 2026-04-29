<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('marketplace_settings')) {
            Schema::create('marketplace_settings', function (Blueprint $table) {
                $table->id();
                $table->string('title')->default('Marketplace');
                $table->text('subtitle')->nullable();
                $table->string('module')->nullable();
                $table->json('config_sections')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_settings');
    }
};