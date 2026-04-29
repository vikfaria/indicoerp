<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('performance_employee_goals')) {
            Schema::create('performance_employee_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable()->index();
                $table->foreignId('goal_type_id')->nullable()->index();
                $table->string('title');
                $table->text('description');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('target', 50);
                $table->decimal('progress', 10, 2)->default(0);
                $table->enum('status', ['not_started', 'in_progress', 'completed', 'overdue'])->default('not_started');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('goal_type_id')->references('id')->on('performance_goal_types')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_employee_goals');
    }
};