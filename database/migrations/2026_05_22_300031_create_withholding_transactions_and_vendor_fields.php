<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const IDX_COMPANY_PERIOD = 'wtt_company_period_idx';
    private const IDX_COMPANY_STATUS = 'wtt_company_status_idx';

    public function up(): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            Schema::create('withholding_tax_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('withholding_rule_id');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->string('vendor_nuit', 9)->nullable();
                $table->string('vendor_name')->nullable();
                $table->date('transaction_date');
                $table->string('document_reference', 50)->nullable();
                $table->decimal('gross_amount', 15, 2);
                $table->decimal('withholding_rate', 5, 2);
                $table->decimal('withholding_amount', 15, 2);
                $table->decimal('net_amount', 15, 2);
                $table->string('fiscal_year', 4);
                $table->unsignedTinyInteger('fiscal_month');
                $table->enum('status', ['pending', 'declared', 'paid'])->default('pending');
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('withholding_rule_id')->references('id')->on('withholding_tax_rules')->onDelete('cascade');
                $table->index(['company_id', 'fiscal_year', 'fiscal_month'], self::IDX_COMPANY_PERIOD);
                $table->index(['company_id', 'status'], self::IDX_COMPANY_STATUS);
            });
        } else {
            Schema::table('withholding_tax_transactions', function (Blueprint $table) {
                if (!$this->indexExists('withholding_tax_transactions', self::IDX_COMPANY_PERIOD)) {
                    $table->index(['company_id', 'fiscal_year', 'fiscal_month'], self::IDX_COMPANY_PERIOD);
                }

                if (!$this->indexExists('withholding_tax_transactions', self::IDX_COMPANY_STATUS)) {
                    $table->index(['company_id', 'status'], self::IDX_COMPANY_STATUS);
                }
            });
        }

        // Add fiscal fields to vendors if table exists
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (!Schema::hasColumn('vendors', 'fiscal_regime')) {
                    $table->string('fiscal_regime', 20)->nullable()->after('tax_number');
                }
                if (!Schema::hasColumn('vendors', 'fiscal_country')) {
                    $table->string('fiscal_country', 2)->default('MZ')->after('fiscal_regime');
                }
                if (!Schema::hasColumn('vendors', 'income_type')) {
                    $table->string('income_type', 30)->nullable()->after('fiscal_country');
                }
                if (!Schema::hasColumn('vendors', 'default_withholding_rule_id')) {
                    $table->unsignedBigInteger('default_withholding_rule_id')->nullable()->after('income_type');
                }
                if (!Schema::hasColumn('vendors', 'is_resident')) {
                    $table->boolean('is_resident')->default(true)->after('default_withholding_rule_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withholding_tax_transactions')) {
            Schema::table('withholding_tax_transactions', function (Blueprint $table) {
                if ($this->indexExists('withholding_tax_transactions', self::IDX_COMPANY_PERIOD)) {
                    $table->dropIndex(self::IDX_COMPANY_PERIOD);
                }

                if ($this->indexExists('withholding_tax_transactions', self::IDX_COMPANY_STATUS)) {
                    $table->dropIndex(self::IDX_COMPANY_STATUS);
                }
            });
        }

        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                $cols = ['fiscal_regime', 'fiscal_country', 'income_type', 'default_withholding_rule_id', 'is_resident'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('vendors', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('withholding_tax_transactions');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?',
            [$indexName]
        );

        return $result !== [];
    }
};
