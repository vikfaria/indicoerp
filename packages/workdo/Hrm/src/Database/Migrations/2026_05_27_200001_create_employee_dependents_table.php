<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_dependents')) {
            Schema::create('employee_dependents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->string('full_name');
                $table->string('relationship', 50);
                $table->date('date_of_birth')->nullable();
                $table->string('document_number', 120)->nullable();
                $table->boolean('is_student')->default(false);
                $table->boolean('is_tax_eligible')->default(true);
                $table->date('valid_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_dependents');
    }
};
