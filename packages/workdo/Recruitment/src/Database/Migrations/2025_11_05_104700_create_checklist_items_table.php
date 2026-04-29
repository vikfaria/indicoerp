<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('checklist_items')) {
            Schema::create('checklist_items', function (Blueprint $table) {
                $table->id();
                $table->string('task_name');
                $table->longText('description')->nullable();
                $table->string('category')->nullable();
                $table->string('assigned_to_role')->nullable();
                $table->integer('due_day')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('status')->default(true);
                $table->foreignId('checklist_id')->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('checklist_id')->references('id')->on('onboarding_checklists')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
