<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fixed_assets')) {
            Schema::create('fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('asset_code', 30)->comment('Internal asset number');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('category', ['tangible', 'intangible', 'investment_property', 'biological']);
                $table->string('sub_category', 50)->nullable()->comment('buildings, equipment, vehicles, software, etc.');
                $table->date('acquisition_date');
                $table->decimal('acquisition_cost', 15, 2);
                $table->decimal('residual_value', 15, 2)->default(0);
                $table->integer('useful_life_months');
                $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production'])->default('straight_line');
                $table->decimal('depreciation_rate', 5, 2)->nullable()->comment('Annual rate %');
                $table->decimal('accumulated_depreciation', 15, 2)->default(0);
                $table->decimal('net_book_value', 15, 2)->default(0);
                $table->decimal('impairment_losses', 15, 2)->default(0);
                $table->decimal('revaluation_surplus', 15, 2)->default(0);
                $table->date('last_depreciation_date')->nullable();
                $table->enum('status', ['active', 'fully_depreciated', 'disposed', 'impaired'])->default('active');
                $table->date('disposal_date')->nullable();
                $table->decimal('disposal_proceeds', 15, 2)->nullable();
                $table->string('location', 100)->nullable();
                $table->string('responsible_person', 100)->nullable();
                $table->string('pgc_asset_account', 20)->nullable()->comment('e.g. 431, 432, 433');
                $table->string('pgc_depreciation_account', 20)->nullable()->comment('e.g. 481');
                $table->string('pgc_expense_account', 20)->nullable()->comment('e.g. 64');
                $table->string('serial_number', 50)->nullable();
                $table->string('supplier', 100)->nullable();
                $table->string('invoice_reference', 50)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['company_id', 'asset_code']);
                $table->index(['company_id', 'status']);
            });
        }

        if (!Schema::hasTable('depreciation_entries')) {
            Schema::create('depreciation_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fixed_asset_id');
                $table->date('depreciation_date');
                $table->string('fiscal_year', 4);
                $table->unsignedTinyInteger('period_number');
                $table->decimal('depreciation_amount', 15, 2);
                $table->decimal('accumulated_after', 15, 2);
                $table->decimal('net_book_value_after', 15, 2);
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
                $table->index(['fixed_asset_id', 'fiscal_year']);
            });
        }

        if (!Schema::hasTable('import_processes')) {
            Schema::create('import_processes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('process_number', 30);
                $table->string('supplier_name');
                $table->string('supplier_country', 2)->default('XX');
                $table->date('import_date');
                $table->string('customs_declaration', 50)->nullable();
                $table->decimal('fob_value', 15, 2)->default(0);
                $table->string('fob_currency', 3)->default('USD');
                $table->decimal('exchange_rate', 15, 6)->default(1);
                $table->decimal('fob_value_mzn', 15, 2)->default(0);
                $table->decimal('freight', 15, 2)->default(0);
                $table->decimal('insurance', 15, 2)->default(0);
                $table->decimal('cif_value', 15, 2)->default(0);
                $table->decimal('customs_duties', 15, 2)->default(0);
                $table->decimal('customs_duty_rate', 5, 2)->default(0);
                $table->decimal('import_vat', 15, 2)->default(0);
                $table->decimal('clearance_fees', 15, 2)->default(0);
                $table->decimal('other_costs', 15, 2)->default(0);
                $table->decimal('total_landed_cost', 15, 2)->default(0);
                $table->enum('status', ['draft', 'in_transit', 'customs', 'cleared', 'received'])->default('draft');
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['company_id', 'process_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_processes');
        Schema::dropIfExists('depreciation_entries');
        Schema::dropIfExists('fixed_assets');
    }
};
