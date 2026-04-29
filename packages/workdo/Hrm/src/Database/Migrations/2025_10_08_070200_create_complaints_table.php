<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('against_employee_id')->nullable();
                $table->foreignId('complaint_type_id')->nullable();
                $table->string('subject');
                $table->longText('description');
                $table->date('complaint_date');
                $table->enum('status', ['pending', 'in review', 'assigned', 'in progress', 'resolved'])->default('pending');
                $table->string('document')->nullable();
                $table->foreignId('resolved_by')->nullable();
                $table->date('resolution_date')->nullable();
                $table->foreignId('creator_id')->nullable();
                $table->foreignId('created_by');
                $table->timestamps();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('against_employee_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('complaint_type_id')->references('id')->on('complaint_types')->onDelete('set null');
                $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
               
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
