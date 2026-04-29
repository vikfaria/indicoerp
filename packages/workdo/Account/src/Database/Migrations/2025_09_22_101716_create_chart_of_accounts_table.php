<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_code');
                $table->string('account_name');
                $table->unsignedBigInteger('account_type_id');
                $table->unsignedBigInteger('parent_account_id')->nullable();
                $table->integer('level')->default(1);
                $table->enum('normal_balance', ['debit', 'credit']);
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system_account')->default(false);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('creator_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');
                $table->foreign('parent_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
