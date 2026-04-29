<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('allowances')) {
            Schema::create('allowances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->foreignId('allowance_type_id')->constrained('allowance_types')->onDelete('cascade');
                $table->enum('type', ['fixed', 'percentage']);
                $table->decimal('amount', 10, 2);
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
        Schema::dropIfExists('allowances');
    }
};
