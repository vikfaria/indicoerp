<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_departments')) {
            Schema::create('event_departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id');;
                $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['event_id', 'department_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_departments');
    }
};
