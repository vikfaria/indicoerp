<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table): void {
                if (!Schema::hasColumn('payrolls', 'total_irps')) {
                    $table->decimal('total_irps', 15, 2)->nullable();
                }

                if (!Schema::hasColumn('payrolls', 'total_inss_employee')) {
                    $table->decimal('total_inss_employee', 15, 2)->nullable();
                }

                if (!Schema::hasColumn('payrolls', 'total_inss_employer')) {
                    $table->decimal('total_inss_employer', 15, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('payroll_entries')) {
            Schema::table('payroll_entries', function (Blueprint $table): void {
                if (!Schema::hasColumn('payroll_entries', 'taxable_income')) {
                    $table->decimal('taxable_income', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'irps_amount')) {
                    $table->decimal('irps_amount', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'inss_employee_rate')) {
                    $table->decimal('inss_employee_rate', 8, 4)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'inss_employee_amount')) {
                    $table->decimal('inss_employee_amount', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'inss_employer_rate')) {
                    $table->decimal('inss_employer_rate', 8, 4)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'inss_employer_amount')) {
                    $table->decimal('inss_employer_amount', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'statutory_deductions_total')) {
                    $table->decimal('statutory_deductions_total', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'statutory_deductions_breakdown')) {
                    $table->json('statutory_deductions_breakdown')->nullable();
                }

                if (!Schema::hasColumn('payroll_entries', 'minimum_wage_required')) {
                    $table->decimal('minimum_wage_required', 15, 2)->nullable();
                }

                if (!Schema::hasColumn('payroll_entries', 'minimum_wage_compliant')) {
                    $table->boolean('minimum_wage_compliant')->default(true);
                }

                if (!Schema::hasColumn('payroll_entries', 'minimum_wage_gap')) {
                    $table->decimal('minimum_wage_gap', 15, 2)->default(0);
                }

                if (!Schema::hasColumn('payroll_entries', 'payroll_sector_code')) {
                    $table->string('payroll_sector_code', 50)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_entries')) {
            Schema::table('payroll_entries', function (Blueprint $table): void {
                $columns = [];

                foreach ([
                    'taxable_income',
                    'irps_amount',
                    'inss_employee_rate',
                    'inss_employee_amount',
                    'inss_employer_rate',
                    'inss_employer_amount',
                    'statutory_deductions_total',
                    'statutory_deductions_breakdown',
                    'minimum_wage_required',
                    'minimum_wage_compliant',
                    'minimum_wage_gap',
                    'payroll_sector_code',
                ] as $column) {
                    if (Schema::hasColumn('payroll_entries', $column)) {
                        $columns[] = $column;
                    }
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table): void {
                $columns = [];

                foreach ([
                    'total_irps',
                    'total_inss_employee',
                    'total_inss_employer',
                ] as $column) {
                    if (Schema::hasColumn('payrolls', $column)) {
                        $columns[] = $column;
                    }
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
