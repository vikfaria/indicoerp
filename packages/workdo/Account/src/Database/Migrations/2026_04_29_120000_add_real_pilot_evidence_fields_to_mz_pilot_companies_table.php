<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mz_pilot_companies')) {
            return;
        }

        Schema::table('mz_pilot_companies', function (Blueprint $table): void {
            if (!Schema::hasColumn('mz_pilot_companies', 'company_nuit')) {
                $table->string('company_nuit', 32)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('mz_pilot_companies', 'validation_result')) {
                $table->enum('validation_result', ['pending', 'passed', 'failed'])->default('pending')->after('pilot_end_date');
            }
            if (!Schema::hasColumn('mz_pilot_companies', 'validation_signed_at')) {
                $table->date('validation_signed_at')->nullable()->after('validation_result');
            }
            if (!Schema::hasColumn('mz_pilot_companies', 'validation_evidence_ref')) {
                $table->string('validation_evidence_ref', 255)->nullable()->after('validation_signed_at');
            }
            if (!Schema::hasColumn('mz_pilot_companies', 'validation_notes')) {
                $table->text('validation_notes')->nullable()->after('validation_evidence_ref');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('mz_pilot_companies')) {
            return;
        }

        Schema::table('mz_pilot_companies', function (Blueprint $table): void {
            $columns = [
                'company_nuit',
                'validation_result',
                'validation_signed_at',
                'validation_evidence_ref',
                'validation_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('mz_pilot_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
