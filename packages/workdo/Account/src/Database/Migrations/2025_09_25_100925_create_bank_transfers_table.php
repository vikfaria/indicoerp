<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bank_transfers')) {
            Schema::create('bank_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number')->unique();
                $table->date('transfer_date');
                $table->foreignId('from_account_id')->constrained('bank_accounts')->onDelete('cascade');
                $table->foreignId('to_account_id')->constrained('bank_accounts')->onDelete('cascade');
                $table->decimal('transfer_amount', 15, 2);
                $table->decimal('transfer_charges', 15, 2)->default(0);
                $table->string('reference_number')->nullable();
                $table->text('description');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->index(['status']);
                $table->index(['transfer_date']);
                $table->index(['from_account_id']);
                $table->index(['to_account_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bank_transfers');
    }
};