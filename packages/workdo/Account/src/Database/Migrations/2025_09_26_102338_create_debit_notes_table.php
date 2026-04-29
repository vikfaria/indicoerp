<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('debit_notes'))
        {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('debit_note_number')->unique();
                $table->date('debit_note_date');
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('return_id')->nullable();
                $table->string('reason');
                $table->enum('status', ['draft', 'approved','partial','applied'])->default('draft');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->decimal('applied_amount', 15, 2)->default(0);
                $table->decimal('balance_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('invoice_id')->references('id')->on('purchase_invoices')->onDelete('set null');
                $table->foreign('return_id')->references('id')->on('purchase_returns')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
    }
};
