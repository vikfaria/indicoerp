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
        if (!Schema::hasTable('deal_calls')) {
            Schema::create('deal_calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->index();
                $table->string('subject');
                $table->string('call_type');
                $table->string('duration');
                $table->foreignId('user_id')->nullable()->index();
                $table->longText('description')->nullable();
                $table->text('call_result')->nullable();
                $table->timestamps();

                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_calls');
    }
};
