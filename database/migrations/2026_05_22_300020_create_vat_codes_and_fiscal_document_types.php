<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mz_vat_codes')) {
            Schema::create('mz_vat_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('description');
                $table->decimal('rate', 5, 2)->default(0);
                $table->enum('type', [
                    'normal', 'zero', 'exempt', 'not_subject',
                    'reverse_charge', 'import', 'digital',
                ])->default('normal');
                $table->text('exemption_reason')->nullable();
                $table->string('saft_tax_code', 10)->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fiscal_document_types')) {
            Schema::create('fiscal_document_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('name');
                $table->string('saft_document_type', 5)->comment('FT, FR, NC, ND, GR, GT, RC, AF');
                $table->enum('category', ['sales', 'purchases', 'payments', 'movements', 'other']);
                $table->boolean('requires_hash')->default(true);
                $table->boolean('requires_series')->default(true);
                $table->boolean('is_credit_document')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fiscal_document_series')) {
            Schema::create('fiscal_document_series', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('fiscal_document_type_id');
                $table->string('series_code', 20);
                $table->string('fiscal_year', 4);
                $table->unsignedInteger('last_sequence')->default(0);
                $table->string('last_hash', 64)->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('fiscal_document_type_id')->references('id')->on('fiscal_document_types')->onDelete('cascade');
                $table->unique(['company_id', 'fiscal_document_type_id', 'series_code', 'fiscal_year'], 'fds_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_document_series');
        Schema::dropIfExists('fiscal_document_types');
        Schema::dropIfExists('mz_vat_codes');
    }
};
