<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('goal_tracking'))
        {
            Schema::create('goal_tracking', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_id')->constrained('goals')->onDelete('cascade');
                $table->date('tracking_date');
                $table->decimal('previous_amount', 15, 2)->default(0);
                $table->decimal('contribution_amount', 15, 2)->default(0);
                $table->decimal('current_amount', 15, 2)->default(0);
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->integer('days_remaining')->default(0);
                $table->date('projected_completion_date')->nullable();
                $table->enum('on_track_status', ['ahead', 'on_track', 'behind', 'critical'])->default('on_track');
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
        Schema::dropIfExists('goal_tracking');
    }
};
