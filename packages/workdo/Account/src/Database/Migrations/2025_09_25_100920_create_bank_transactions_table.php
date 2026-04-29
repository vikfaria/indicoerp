<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bank_transactions')) {
            Schema::create('bank_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_account_id');
                $table->date('transaction_date');
                $table->enum('transaction_type', ['debit', 'credit']);
                $table->string('reference_number')->nullable();
                $table->text('description');
                $table->decimal('amount', 15, 2);
                $table->decimal('running_balance', 15, 2);
                $table->enum('transaction_status', ['pending', 'cleared', 'cancelled'])->default('pending');
                $table->enum('reconciliation_status', ['unreconciled', 'reconciled'])->default('unreconciled');
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                // Foreign keys removed to avoid constraint issues
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bank_transactions');
    }
};
