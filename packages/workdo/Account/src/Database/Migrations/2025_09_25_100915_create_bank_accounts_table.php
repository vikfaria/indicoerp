<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('bank_accounts'))
        {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_number');
                $table->string('account_name');
                $table->string('bank_name');
                $table->string('branch_name')->nullable();
                $table->string('account_type')->default('0');
                $table->string('payment_gateway')->nullable();
                $table->decimal('opening_balance', 10, 2)->nullable();
                $table->decimal('current_balance', 10, 2)->nullable();
                $table->string('iban')->nullable();
                $table->string('swift_code')->nullable();
                $table->string('routing_number')->nullable();
                $table->boolean('is_active')->default(false);

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
        Schema::dropIfExists('bank_accounts');
    }
};