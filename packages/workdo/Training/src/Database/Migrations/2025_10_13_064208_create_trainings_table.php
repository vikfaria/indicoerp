<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('trainings'))
        {
            Schema::create('trainings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('description')->nullable();
                $table->foreignId('training_type_id')->nullable()->constrained('training_types')->onDelete('set null');
                $table->foreignId('trainer_id')->nullable()->constrained('trainers')->onDelete('set null');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->date('start_date');
                $table->date('end_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('location')->nullable();
                $table->integer('max_participants')->nullable();
                $table->decimal('cost', 10, 2)->nullable();
                $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
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
        Schema::dropIfExists('trainings');
    }
};