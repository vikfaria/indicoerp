<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('balance_sheet_notes')) {
            Schema::create('balance_sheet_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('balance_sheet_id')->constrained('balance_sheets')->onDelete('cascade');
                $table->integer('note_number');
                $table->string('note_title');
                $table->text('note_content');
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
        Schema::dropIfExists('balance_sheet_notes');
    }
};