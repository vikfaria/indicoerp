<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->foreignId('holiday_type_id')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_paid')->default(1);
                $table->boolean('is_sync_google_calendar')->default(0);
                $table->boolean('is_sync_outlook_calendar')->default(0);
                $table->foreignId('creator_id')->nullable();
                $table->foreignId('created_by');

                $table->foreign('holiday_type_id')->references('id')->on('holiday_types')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
