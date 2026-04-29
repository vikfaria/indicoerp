<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acknowledgments')) {
            Schema::create('acknowledgments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('document_id')->nullable();
                $table->enum('status', ['pending', 'acknowledged'])->default('pending');
                $table->text('acknowledgment_note')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->foreignId('assigned_by')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('document_id')->references('id')->on('hrm_documents')->onDelete('set null');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acknowledgments');
    }
};
