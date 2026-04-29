<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('form_conversions')) {
            Schema::create('form_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_id')->constrained('forms')->onDelete('cascade');
                $table->string('module_name');
                $table->string('submodule_name')->nullable(); 
                $table->boolean('is_active')->default(false);
                $table->json('field_mappings'); 
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->unique(['form_id'], 'unique_form_conversion');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('form_conversions');
    }
};