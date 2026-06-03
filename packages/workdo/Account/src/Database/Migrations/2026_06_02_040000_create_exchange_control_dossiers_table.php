<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exchange_control_dossiers')) {
            return;
        }

        Schema::create('exchange_control_dossiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('direction', 20); // outbound|inbound
            $table->string('payment_type', 40); // vendor_payment|customer_payment
            $table->unsignedBigInteger('payment_id');
            $table->string('payment_reference', 120)->nullable();
            $table->date('operation_date')->nullable();
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_country', 120)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('amount_mzn', 15, 2)->nullable();
            $table->json('documents')->nullable();
            $table->json('required_documents')->nullable();
            $table->json('missing_documents')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'direction'], 'ecd_company_direction_idx');
            $table->index(['company_id', 'payment_type', 'payment_id'], 'ecd_company_payment_idx');
            $table->unique(['company_id', 'direction', 'payment_type', 'payment_id'], 'ecd_company_dir_payment_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_control_dossiers');
    }
};
