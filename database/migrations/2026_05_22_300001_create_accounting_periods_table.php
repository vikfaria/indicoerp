<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_periods')) {
            Schema::create('accounting_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('fiscal_year', 4);
                $table->unsignedTinyInteger('period_number')->comment('1-12 = months, 13 = closing adjustments');
                $table->string('period_name', 50);
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['open', 'closing', 'closed'])->default('open');
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->text('close_checklist')->nullable()->comment('JSON checklist state');
                $table->text('reopen_reason')->nullable();
                $table->unsignedBigInteger('reopened_by')->nullable();
                $table->timestamp('reopened_at')->nullable();
                $table->json('snapshot')->nullable()->comment('Balances snapshot at close');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('reopened_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['company_id', 'fiscal_year', 'period_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
