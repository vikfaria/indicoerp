<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_invoices')) {
            return;
        }

        Schema::table('sales_invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('sales_invoices', 'operation_date')) {
                $table->date('operation_date')->nullable()->after('invoice_date');
            }

            if (!Schema::hasColumn('sales_invoices', 'fiscal_issue_deadline')) {
                $table->date('fiscal_issue_deadline')->nullable()->after('operation_date');
            }

            if (!Schema::hasColumn('sales_invoices', 'issued_with_delay')) {
                $table->boolean('issued_with_delay')->default(false)->after('fiscal_issue_deadline');
            }

            if (!Schema::hasColumn('sales_invoices', 'late_issue_reason')) {
                $table->text('late_issue_reason')->nullable()->after('issued_with_delay');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_invoices')) {
            return;
        }

        $columns = [
            'operation_date',
            'fiscal_issue_deadline',
            'issued_with_delay',
            'late_issue_reason',
        ];

        $dropColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('sales_invoices', $column)));

        if ($dropColumns === []) {
            return;
        }

        Schema::table('sales_invoices', function (Blueprint $table) use ($dropColumns): void {
            $table->dropColumn($dropColumns);
        });
    }
};
