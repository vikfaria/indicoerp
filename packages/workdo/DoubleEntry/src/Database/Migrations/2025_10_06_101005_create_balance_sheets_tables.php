<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('balance_sheets')) {
            Schema::create('balance_sheets', function (Blueprint $table) {
                $table->id();
                $table->date('balance_sheet_date');
                $table->string('financial_year');
                $table->decimal('total_assets', 15, 2)->default(0);
                $table->decimal('total_liabilities', 15, 2)->default(0);
                $table->decimal('total_equity', 15, 2)->default(0);
                $table->boolean('is_balanced')->default(false);
                $table->enum('status', ['draft', 'finalized'])->default('draft');
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
        Schema::dropIfExists('balance_sheets');
    }
};