<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_id', 100)->unique();
                $table->string('name');
                $table->string('email');
                $table->foreignId('user_id')->nullable()->index();
                $table->string('account_type');
                $table->foreignId('category')->nullable()->index();
                $table->string('subject');
                $table->string('status');
                $table->longText('description')->nullable();
                $table->longText('attachments')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('category')->references('id')->on('ticket_categories')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};