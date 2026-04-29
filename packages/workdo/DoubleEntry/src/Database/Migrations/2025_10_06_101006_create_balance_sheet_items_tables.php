<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('balance_sheet_items')) {
            Schema::create('balance_sheet_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('balance_sheet_id')->constrained('balance_sheets')->onDelete('cascade');
                $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
                $table->enum('section_type', ['assets', 'liabilities', 'equity', 'other']);
                $table->enum('sub_section', ['current_assets', 'fixed_assets', 'other_assets', 'current_liabilities', 'long_term_liabilities', 'equity', 'other']);
                $table->decimal('amount', 15, 2);
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
        Schema::dropIfExists('balance_sheet_items');
    }
};