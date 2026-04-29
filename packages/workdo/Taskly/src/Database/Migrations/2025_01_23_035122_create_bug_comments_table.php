<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bug_comments')) {
            Schema::create('bug_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bug_id');
                $table->unsignedBigInteger('user_id');
                $table->text('comment');
                $table->timestamps();

                $table->foreign('bug_id')->references('id')->on('project_bugs')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bug_comments');
    }
};
