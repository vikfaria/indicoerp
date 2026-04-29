<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('support_ticket_quick_links')) {
            Schema::create('support_ticket_quick_links', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('icon');
                $table->string('link');
                $table->integer('order')->default(0);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('support_ticket_quick_links');
    }
};