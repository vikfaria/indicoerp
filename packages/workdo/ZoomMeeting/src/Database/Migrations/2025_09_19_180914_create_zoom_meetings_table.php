<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('zoom_meetings'))
        {
            Schema::create('zoom_meetings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('description')->nullable();
                $table->string('meeting_id')->nullable();
                $table->string('meeting_password')->nullable();
                $table->text('start_url')->nullable();
                $table->text('join_url')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->integer('duration')->nullable();
                $table->boolean('host_video')->default(false);
                $table->boolean('participant_video')->default(false);
                $table->boolean('waiting_room')->default(false);
                $table->boolean('recording')->default(false);
                $table->string('status')->default('Scheduled');
                $table->json('participants')->nullable();
                $table->foreignId('host_id')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('zoom_meetings');
    }
};