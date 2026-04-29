<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('webhook_modules')) {
            Schema::create('webhook_modules', function (Blueprint $table) {
                $table->id();
                $table->string('module');
                $table->string('submodule');
                $table->string('type')->default('company');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('webhook_modules');
    }
};