<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('expenses'))
        {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->string('expense_number');
                $table->date('expense_date');
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('bank_account_id');
                $table->unsignedBigInteger('chart_of_account_id')->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
                $table->text('description')->nullable();
                $table->foreignId('approved_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('expense_categories');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
                $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};
