<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_questions')) {
            Schema::create('custom_questions', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->enum('type', ['text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number'])->default('text');
                $table->longText('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true); // 0 = inactive, 1 = active
                $table->integer('sort_order')->nullable();

                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_questions');
    }
};
