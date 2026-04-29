<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('credit_notes'))
        {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('credit_note_number');
                $table->date('credit_note_date');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('return_id')->nullable();
                $table->string('reason');
                $table->enum('status', ['draft', 'approved', 'partial', 'applied', 'cancelled'])->default('draft');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->decimal('applied_amount', 15, 2)->default(0);
                $table->decimal('balance_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('invoice_id')->references('id')->on('sales_invoices')->onDelete('set null');
                $table->foreign('return_id')->references('id')->on('sales_invoice_returns')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->index(['status', 'credit_note_date']);
                $table->index('customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
