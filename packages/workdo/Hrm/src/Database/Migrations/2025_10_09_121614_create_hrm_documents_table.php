<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hrm_documents')) {
            Schema::create('hrm_documents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('description')->nullable();
                $table->foreignId('document_category_id')->nullable();
                $table->string('document')->nullable();
                $table->date('effective_date')->nullable();
                $table->enum('status', ['pending', 'approve', 'reject'])->default('pending');
                $table->foreignId('uploaded_by')->nullable();
                $table->foreignId('approved_by')->nullable();

                $table->foreign('document_category_id')->references('id')->on('document_categories')->onDelete('set null');
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

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
        Schema::dropIfExists('hrm_documents');
    }
};
