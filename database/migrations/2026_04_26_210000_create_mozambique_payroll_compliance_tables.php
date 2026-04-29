<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mz_irps_tables')) {
            Schema::create('mz_irps_tables', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['effective_from', 'effective_to', 'is_active'], 'mz_irps_tables_effective_idx');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('mz_irps_brackets')) {
            Schema::create('mz_irps_brackets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('irps_table_id')->index();
                $table->decimal('range_from', 15, 2)->default(0);
                $table->decimal('range_to', 15, 2)->nullable();
                $table->decimal('fixed_amount', 15, 2)->default(0);
                $table->decimal('rate_percent', 8, 4)->default(0);
                $table->unsignedInteger('sequence')->default(0);
                $table->timestamps();

                $table->index(['irps_table_id', 'sequence'], 'mz_irps_brackets_seq_idx');
                $table->foreign('irps_table_id')->references('id')->on('mz_irps_tables')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('mz_inss_rates')) {
            Schema::create('mz_inss_rates', function (Blueprint $table): void {
                $table->id();
                $table->decimal('employee_rate', 8, 4)->default(3.0000);
                $table->decimal('employer_rate', 8, 4)->default(4.0000);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['effective_from', 'effective_to', 'is_active'], 'mz_inss_rates_effective_idx');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('mz_minimum_wages')) {
            Schema::create('mz_minimum_wages', function (Blueprint $table): void {
                $table->id();
                $table->string('sector_code', 50);
                $table->string('sector_name', 120);
                $table->decimal('monthly_amount', 15, 2);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['sector_code', 'effective_from', 'effective_to'], 'mz_minimum_wages_sector_effective_idx');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mz_minimum_wages');
        Schema::dropIfExists('mz_inss_rates');
        Schema::dropIfExists('mz_irps_brackets');
        Schema::dropIfExists('mz_irps_tables');
    }
};
