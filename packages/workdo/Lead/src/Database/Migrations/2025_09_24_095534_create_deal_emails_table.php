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
        if (!Schema::hasTable('deal_emails')) {

            Schema::create('deal_emails', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->index();
                $table->string('to');
                $table->string('subject');
                $table->text('description');
                $table->timestamps();

                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_emails');
    }
};
