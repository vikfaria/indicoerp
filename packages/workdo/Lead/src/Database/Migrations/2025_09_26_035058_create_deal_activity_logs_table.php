<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('deal_activity_logs'))
        {
            Schema::create('deal_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->foreignId('deal_id')->index();
                $table->string('log_type');
                $table->text('remark')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deal_activity_logs');
    }
};