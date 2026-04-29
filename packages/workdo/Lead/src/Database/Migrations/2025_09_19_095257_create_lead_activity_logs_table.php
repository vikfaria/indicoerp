<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lead_activity_logs')) {
            Schema::create('lead_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->foreignId('lead_id')->index();
                $table->string('log_type');
                $table->text('remark')->nullable();
                $table->timestamps();

                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lead_activity_logs');
    }
};
