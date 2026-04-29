<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('debit_note_applications'))
        {
            Schema::create('debit_note_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('debit_note_id');
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->decimal('applied_amount', 15, 2);
                $table->date('application_date');
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('debit_note_id')->references('id')->on('debit_notes')->onDelete('cascade');
                $table->foreign('payment_id')->references('id')->on('vendor_payments')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_applications');
    }
};