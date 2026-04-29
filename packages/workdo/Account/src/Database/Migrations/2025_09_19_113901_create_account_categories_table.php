<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_categories')) {
            Schema::create('account_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code');
                $table->enum('type', ['assets', 'liabilities', 'equity', 'revenue', 'expenses']);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_categories');
    }
};
