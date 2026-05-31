<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addFieldsToTable('terminations');
        $this->addFieldsToTable('resignations');
    }

    public function down(): void
    {
        $this->dropFieldsFromTable('terminations');
        $this->dropFieldsFromTable('resignations');
    }

    private function addFieldsToTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (!Schema::hasColumn($tableName, 'legal_notice_required_days')) {
                $table->unsignedInteger('legal_notice_required_days')->nullable()->after('status');
            }
            if (!Schema::hasColumn($tableName, 'legal_notice_provided_days')) {
                $table->unsignedInteger('legal_notice_provided_days')->nullable()->after('legal_notice_required_days');
            }
            if (!Schema::hasColumn($tableName, 'legal_notice_missing_days')) {
                $table->unsignedInteger('legal_notice_missing_days')->nullable()->after('legal_notice_provided_days');
            }
            if (!Schema::hasColumn($tableName, 'legal_notice_compliant')) {
                $table->boolean('legal_notice_compliant')->nullable()->after('legal_notice_missing_days');
            }

            if (!Schema::hasColumn($tableName, 'settlement_base_salary_amount')) {
                $table->decimal('settlement_base_salary_amount', 15, 2)->nullable()->after('legal_notice_compliant');
            }
            if (!Schema::hasColumn($tableName, 'settlement_daily_salary_amount')) {
                $table->decimal('settlement_daily_salary_amount', 15, 2)->nullable()->after('settlement_base_salary_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_salary_until_exit_amount')) {
                $table->decimal('settlement_salary_until_exit_amount', 15, 2)->nullable()->after('settlement_daily_salary_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_unused_leave_days')) {
                $table->decimal('settlement_unused_leave_days', 8, 2)->nullable()->after('settlement_salary_until_exit_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_unused_leave_amount')) {
                $table->decimal('settlement_unused_leave_amount', 15, 2)->nullable()->after('settlement_unused_leave_days');
            }
            if (!Schema::hasColumn($tableName, 'settlement_other_earnings_amount')) {
                $table->decimal('settlement_other_earnings_amount', 15, 2)->nullable()->after('settlement_unused_leave_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_other_deductions_amount')) {
                $table->decimal('settlement_other_deductions_amount', 15, 2)->nullable()->after('settlement_other_earnings_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_apply_indemnity')) {
                $table->boolean('settlement_apply_indemnity')->nullable()->after('settlement_other_deductions_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_indemnity_days_per_year')) {
                $table->decimal('settlement_indemnity_days_per_year', 8, 2)->nullable()->after('settlement_apply_indemnity');
            }
            if (!Schema::hasColumn($tableName, 'settlement_indemnity_years')) {
                $table->decimal('settlement_indemnity_years', 10, 4)->nullable()->after('settlement_indemnity_days_per_year');
            }
            if (!Schema::hasColumn($tableName, 'settlement_indemnity_amount')) {
                $table->decimal('settlement_indemnity_amount', 15, 2)->nullable()->after('settlement_indemnity_years');
            }
            if (!Schema::hasColumn($tableName, 'settlement_gross_amount')) {
                $table->decimal('settlement_gross_amount', 15, 2)->nullable()->after('settlement_indemnity_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_total_deductions_amount')) {
                $table->decimal('settlement_total_deductions_amount', 15, 2)->nullable()->after('settlement_gross_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_net_amount')) {
                $table->decimal('settlement_net_amount', 15, 2)->nullable()->after('settlement_total_deductions_amount');
            }
            if (!Schema::hasColumn($tableName, 'settlement_generated_at')) {
                $table->timestamp('settlement_generated_at')->nullable()->after('settlement_net_amount');
            }
        });
    }

    private function dropFieldsFromTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $columns = [
                'legal_notice_required_days',
                'legal_notice_provided_days',
                'legal_notice_missing_days',
                'legal_notice_compliant',
                'settlement_base_salary_amount',
                'settlement_daily_salary_amount',
                'settlement_salary_until_exit_amount',
                'settlement_unused_leave_days',
                'settlement_unused_leave_amount',
                'settlement_other_earnings_amount',
                'settlement_other_deductions_amount',
                'settlement_apply_indemnity',
                'settlement_indemnity_days_per_year',
                'settlement_indemnity_years',
                'settlement_indemnity_amount',
                'settlement_gross_amount',
                'settlement_total_deductions_amount',
                'settlement_net_amount',
                'settlement_generated_at',
            ];

            $dropColumns = array_values(array_filter($columns, static fn(string $column): bool => Schema::hasColumn($tableName, $column)));
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
