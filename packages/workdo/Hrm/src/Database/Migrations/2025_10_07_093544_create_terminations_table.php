<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('terminations')) {
            Schema::create('terminations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('termination_type_id')->nullable();
                $table->date('notice_date')->nullable();
                $table->date('termination_date')->nullable();
                $table->string('reason');
                $table->longText('description')->nullable();
                $table->string('document')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();


                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('termination_type_id')->references('id')->on('termination_types')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terminations');
    }
};
