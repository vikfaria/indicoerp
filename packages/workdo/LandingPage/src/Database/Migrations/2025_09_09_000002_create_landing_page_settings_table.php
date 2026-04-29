<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('landing_page_settings')) {
            Schema::create('landing_page_settings', function (Blueprint $table) {
                $table->id();
                $table->string('company_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->text('contact_address')->nullable();
                $table->json('config_sections')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('landing_page_settings');
    }
};