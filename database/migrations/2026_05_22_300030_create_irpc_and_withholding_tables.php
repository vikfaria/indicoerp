<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('irpc_configurations')) {
            Schema::create('irpc_configurations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('fiscal_year', 4);
                $table->decimal('standard_rate', 5, 2)->default(32.00)->comment('Taxa normal IRPC');
                $table->decimal('reduced_rate', 5, 2)->nullable()->comment('Taxa reduzida se aplicável');
                $table->enum('regime', ['normal', 'simplified', 'free_zone', 'agriculture'])->default('normal');
                $table->decimal('payment_on_account_rate', 5, 2)->default(80.00)->comment('% do imposto anterior para PPC');
                $table->boolean('is_first_year')->default(false);
                $table->json('fiscal_incentives')->nullable()->comment('Incentivos fiscais aplicáveis');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['company_id', 'fiscal_year']);
            });
        }

        if (!Schema::hasTable('tax_adjustments')) {
            Schema::create('tax_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('fiscal_year', 4);
                $table->enum('type', ['add_back', 'deduction'])->comment('Acréscimo ou dedução fiscal');
                $table->string('category', 50)->comment('depreciation, provisions, fines, entertainment, donations, etc.');
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->string('legal_basis', 100)->nullable()->comment('Artigo legal');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['company_id', 'fiscal_year']);
            });
        }

        if (!Schema::hasTable('withholding_tax_rules')) {
            Schema::create('withholding_tax_rules', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->enum('income_type', [
                    'services', 'rents', 'royalties', 'interest',
                    'dividends', 'capital_gains', 'commissions',
                    'management_fees', 'technical_assistance', 'other',
                ]);
                $table->decimal('rate', 5, 2);
                $table->enum('applies_to', ['resident', 'non_resident', 'both'])->default('both');
                $table->boolean('is_final_tax')->default(false)->comment('Retenção definitiva vs. por conta');
                $table->string('legal_basis', 100)->nullable();
                $table->string('pgc_debit_account', 20)->nullable()->comment('Conta PGC do gasto');
                $table->string('pgc_credit_account', 20)->nullable()->comment('Conta PGC da retenção a pagar');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_rules');
        Schema::dropIfExists('tax_adjustments');
        Schema::dropIfExists('irpc_configurations');
    }
};
