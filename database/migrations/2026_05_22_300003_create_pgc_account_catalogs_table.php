<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pgc_account_catalogs')) {
            Schema::create('pgc_account_catalogs', function (Blueprint $table) {
                $table->id();
                $table->enum('framework', ['pgc_nirf', 'pgc_pe', 'ispc'])->default('pgc_nirf');
                $table->string('version', 10)->default('2025');
                $table->unsignedTinyInteger('class_number')->comment('0-9');
                $table->string('account_code', 20);
                $table->string('account_name');
                $table->string('parent_code', 20)->nullable();
                $table->unsignedTinyInteger('level')->default(1);
                $table->enum('normal_balance', ['debit', 'credit']);
                $table->boolean('is_movement_account')->default(false);
                $table->string('tax_type', 30)->nullable();
                $table->string('financial_statement_line', 50)->nullable();
                $table->string('modelo20_line', 50)->nullable();
                $table->string('saft_taxonomy_code', 20)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['framework', 'version', 'account_code']);
                $table->index(['framework', 'class_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pgc_account_catalogs');
    }
};
