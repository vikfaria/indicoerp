<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_journals')) {
            Schema::create('accounting_journals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('code', 10)->comment('e.g. CX, BK, VD, CP, SL');
                $table->string('name');
                $table->enum('type', [
                    'cash', 'bank', 'purchases', 'sales', 'salaries',
                    'adjustments', 'opening', 'closing', 'fixed_assets',
                    'fiscal', 'general', 'inventory',
                ])->default('general');
                $table->unsignedBigInteger('default_debit_account_id')->nullable();
                $table->unsignedBigInteger('default_credit_account_id')->nullable();
                $table->boolean('requires_attachment')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('numbering_prefix', 10)->nullable()->comment('Prefix for journal numbers');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('default_debit_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
                $table->foreign('default_credit_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['company_id', 'code']);
            });
        }

        // Add journal reference and period to journal_entries
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('journal_entries', 'accounting_journal_id')) {
                    $table->unsignedBigInteger('accounting_journal_id')->nullable()->after('id');
                    $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->onDelete('set null');
                }
                if (!Schema::hasColumn('journal_entries', 'accounting_period_id')) {
                    $table->unsignedBigInteger('accounting_period_id')->nullable()->after('accounting_journal_id');
                    $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('set null');
                }
                if (!Schema::hasColumn('journal_entries', 'fiscal_year')) {
                    $table->string('fiscal_year', 4)->nullable()->after('accounting_period_id');
                }
                if (!Schema::hasColumn('journal_entries', 'period_number')) {
                    $table->unsignedTinyInteger('period_number')->nullable()->after('fiscal_year');
                }
                if (!Schema::hasColumn('journal_entries', 'document_support')) {
                    $table->string('document_support')->nullable()->after('description')
                        ->comment('Reference to supporting document');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $cols = ['accounting_journal_id', 'accounting_period_id', 'fiscal_year', 'period_number', 'document_support'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('journal_entries', $col)) {
                        if (in_array($col, ['accounting_journal_id', 'accounting_period_id'])) {
                            $table->dropForeign([$col]);
                        }
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('accounting_journals');
    }
};
