<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timesheets')) {
            Schema::create('timesheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->foreignId('project_id')->nullable()->index();
                $table->foreignId('task_id')->nullable()->index();
                $table->date('date');
                $table->integer('hours')->default(0);
                $table->integer('minutes')->default(0);
                $table->text('notes')->nullable();
                $table->enum('type', ['clock_in_out', 'project', 'manual'])->default('manual');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                
                // Add foreign keys only if tables exist
                if (\Schema::hasTable('projects')) {
                    $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
                }
                if (\Schema::hasTable('project_tasks')) {
                    $table->foreign('task_id')->references('id')->on('project_tasks')->onDelete('set null');
                }
                
                $table->index(['user_id', 'date']);
                $table->index(['project_id', 'task_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};