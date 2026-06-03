<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withholding_tax_treaty_rates')) {
            return;
        }

        Schema::create('withholding_tax_treaty_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->nullable()->index();
            $table->string('country_code', 3)->nullable()->index();
            $table->string('country_name', 120)->nullable()->index();
            $table->string('income_type', 60)->default('all')->index();
            $table->decimal('standard_rate', 8, 4)->nullable();
            $table->decimal('treaty_rate', 8, 4);
            $table->boolean('requires_residency_certificate')->default(true);
            $table->string('legal_basis', 255)->nullable();
            $table->date('valid_from')->nullable()->index();
            $table->date('valid_to')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_treaty_rates');
    }
};
