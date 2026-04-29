<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('comparative_balance_sheets')) {
            Schema::create('comparative_balance_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('current_period_id')->constrained('balance_sheets')->onDelete('cascade');
                $table->foreignId('previous_period_id')->constrained('balance_sheets')->onDelete('cascade');
                $table->date('comparison_date');
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
        Schema::dropIfExists('comparative_balance_sheets');
    }
};