<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('trainers'))
        {
            Schema::create('trainers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('contact', 20);
                $table->string('email')->unique();
                $table->string('experience');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->longText('expertise')->nullable();
                $table->longText('qualification')->nullable();
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
        Schema::dropIfExists('trainers');
    }
};