<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('overtimes')) {
            Schema::create('overtimes', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->foreignId('employee_id');
                $table->integer('total_days');
                $table->decimal('hours', 8, 2);
                $table->decimal('rate', 10, 2);
                $table->date('start_date');
                $table->date('end_date');
                $table->text('notes')->nullable();
                $table->enum('status', ['active', 'expired'])->default('active');
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
        Schema::dropIfExists('overtimes');
    }
};
