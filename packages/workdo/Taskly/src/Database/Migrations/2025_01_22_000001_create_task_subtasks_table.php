<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_subtasks')) {
            Schema::create('task_subtasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('project_tasks')->onDelete('cascade');
                $table->string('name');
                $table->date('due_date')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_subtasks');
    }
};
