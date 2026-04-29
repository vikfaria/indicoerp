<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('training_tasks'))
        {
            Schema::create('training_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');
                $table->string('title');
                $table->longText('description')->nullable();
                $table->enum('status', ['pending', 'completed'])->default('pending');
                $table->date('due_date')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('training_tasks');
    }
};