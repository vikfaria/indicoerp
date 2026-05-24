<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_fiscal_profiles')) {
            Schema::create('company_fiscal_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('nuit', 9)->nullable();
                $table->enum('fiscal_regime', ['normal', 'simplified', 'exempt'])->default('normal');
                $table->enum('entity_classification', ['large', 'medium', 'small', 'micro', 'ispc'])->default('small');
                $table->enum('accounting_framework', ['pgc_nirf', 'pgc_pe', 'ispc'])->default('pgc_nirf');
                $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
                $table->string('economic_activity_code', 20)->nullable();
                $table->string('economic_activity_description')->nullable();
                $table->string('business_license_number', 50)->nullable();
                $table->date('license_expiry_date')->nullable();
                $table->string('entity_type', 50)->nullable()->comment('SA, Lda, EI, etc.');
                $table->json('structured_bank_details')->nullable();
                $table->string('tax_office', 100)->nullable();
                $table->string('province', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['company_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fiscal_profiles');
    }
};
