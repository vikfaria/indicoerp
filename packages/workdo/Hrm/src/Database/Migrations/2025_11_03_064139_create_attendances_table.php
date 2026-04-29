<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
                $table->date('date');
                $table->dateTime('clock_in');
                $table->dateTime('clock_out')->nullable();
                $table->decimal('break_hour', 8, 2)->nullable()->default(0);
                $table->decimal('total_hour', 8, 2)->nullable()->default(0);
                $table->decimal('overtime_hours', 8, 2)->nullable()->default(0);
                $table->decimal('overtime_amount', 10, 2)->nullable()->default(0);
                $table->enum('status', ['present', 'half day', 'absent'])->default('present');
                $table->longText('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade'); 
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
