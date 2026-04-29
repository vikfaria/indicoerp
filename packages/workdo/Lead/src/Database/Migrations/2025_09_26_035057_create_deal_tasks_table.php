<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('deal_tasks'))
        {
            Schema::create('deal_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->index();
                $table->string('name');
                $table->date('date');
                $table->time('time');
                $table->string('priority');
                $table->string('status');
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
                $table->foreign('creator_id', 'deal_tasks_creator_id_foreign')->references('id')->on('users');
                $table->foreign('created_by', 'deal_tasks_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deal_tasks');
    }
};