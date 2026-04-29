<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_bugs')) {
            Schema::create('project_bugs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->index();
                $table->string('title');
                $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
                $table->json('assigned_to')->nullable();
                $table->foreignId('stage_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $table->foreign('stage_id')->references('id')->on('bug_stages')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_bugs');
    }
};
