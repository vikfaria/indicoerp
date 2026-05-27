<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (!Schema::hasColumn('contracts', 'is_labour_contract')) {
                    $table->boolean('is_labour_contract')->default(false)->after('source_type')->index();
                }

                if (!Schema::hasColumn('contracts', 'legal_contract_type')) {
                    $table->string('legal_contract_type', 50)->nullable()->after('is_labour_contract')->index();
                }

                if (!Schema::hasColumn('contracts', 'fixed_term_justification')) {
                    $table->text('fixed_term_justification')->nullable()->after('legal_contract_type');
                }

                if (!Schema::hasColumn('contracts', 'probation_category')) {
                    $table->string('probation_category', 40)->nullable()->after('fixed_term_justification');
                }

                if (!Schema::hasColumn('contracts', 'legal_notes')) {
                    $table->text('legal_notes')->nullable()->after('probation_category');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                foreach ([
                    'is_labour_contract',
                    'legal_contract_type',
                    'fixed_term_justification',
                    'probation_category',
                    'legal_notes',
                ] as $column) {
                    if (Schema::hasColumn('contracts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
