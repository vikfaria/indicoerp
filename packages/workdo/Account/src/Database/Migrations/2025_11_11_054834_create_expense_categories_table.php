<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('expense_categories'))
        {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('category_name');
                $table->string('category_code');
                $table->string('description')->nullable();
                $table->string('is_active');
                $table->foreignId('gl_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
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
        Schema::dropIfExists('expense_categories');
    }
};