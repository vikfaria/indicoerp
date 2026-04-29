<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('lead_tasks'))
        {
            Schema::create('lead_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->index();
                $table->string('name');
                $table->date('date');
                $table->time('time');
                $table->string('priority');
                $table->string('status');
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
                $table->foreign('creator_id', 'lead_tasks_creator_id_foreign')->references('id')->on('users');
                $table->foreign('created_by', 'lead_tasks_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lead_tasks');
    }
};