<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcement_departments')) {
            Schema::create('announcement_departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
                $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['announcement_id', 'department_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_departments');
    }
};
