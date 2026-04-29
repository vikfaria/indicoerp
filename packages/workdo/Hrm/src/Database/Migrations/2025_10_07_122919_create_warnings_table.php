<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warnings')) {
            Schema::create('warnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('warning_by')->nullable();
                $table->foreignId('warning_type_id')->nullable()->constrained('warning_types')->onDelete('set null');
                $table->string('subject');
                $table->string('severity');
                $table->date('warning_date')->nullable();
                $table->longText('description')->nullable();
                $table->string('document')->nullable();
                $table->enum('status', [
                    'pending',
                    'approved',
                    'rejected',
                ])->default('pending');
                $table->string('employee_response')->nullable();

                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('warning_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};
