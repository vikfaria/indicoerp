<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deal_discussions')) {
            Schema::create('deal_discussions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->index();
                $table->longText('comment');
                $table->foreignId('creator_id')->index();
                $table->foreignId('created_by')->index();
                $table->timestamps();

                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_discussions');
    }
};
