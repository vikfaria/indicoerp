<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('goal_milestones'))
        {
            Schema::create('goal_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_id')->constrained('goals')->onDelete('cascade');
                $table->string('milestone_name');
                $table->longText('milestone_description')->nullable();
                $table->decimal('target_amount', 15, 2);
                $table->date('target_date');
                $table->date('achieved_date')->nullable();
                $table->decimal('achieved_amount', 15, 2)->default(0);
                $table->enum('status', ['pending','achieved'])->default('pending');

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
        Schema::dropIfExists('goal_milestones');
    }
};
