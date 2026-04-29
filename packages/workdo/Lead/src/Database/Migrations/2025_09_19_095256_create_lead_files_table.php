<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('lead_files')){
            Schema::create('lead_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->index();
                $table->string('file_name');
                $table->string('file_path');
                $table->timestamps();
                
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lead_files');
    }
};