<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('deal_files')){
            Schema::create('deal_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->index();
                $table->string('file_name');
                $table->string('file_path');
                $table->timestamps();
                
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deal_files');
    }
};