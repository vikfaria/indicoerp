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
        if (!Schema::hasTable('client_permissions')) {
            Schema::create('client_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->index();
                $table->foreignId('deal_id')->index();
                $table->json('permissions')->nullable();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
                $table->unique(['client_id', 'deal_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_permissions');
    }
};
