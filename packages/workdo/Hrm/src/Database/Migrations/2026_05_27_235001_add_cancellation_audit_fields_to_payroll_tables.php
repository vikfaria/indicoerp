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
                if (!Schema::hasColumn('payrolls', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('is_payroll_paid');
                }

                if (!Schema::hasColumn('payrolls', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('payrolls', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('cancelled_by');
                }
            });
        }

        if (Schema::hasTable('payroll_entries')) {
            Schema::table('payroll_entries', function (Blueprint $table): void {
                if (!Schema::hasColumn('payroll_entries', 'is_cancelled')) {
                    $table->boolean('is_cancelled')->default(false)->after('status');
                }

                if (!Schema::hasColumn('payroll_entries', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('is_cancelled');
                }

                if (!Schema::hasColumn('payroll_entries', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('payroll_entries', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('cancelled_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_entries')) {
            Schema::table('payroll_entries', function (Blueprint $table): void {
                if (Schema::hasColumn('payroll_entries', 'cancellation_reason')) {
                    $table->dropColumn('cancellation_reason');
                }

                if (Schema::hasColumn('payroll_entries', 'cancelled_by')) {
                    $table->dropConstrainedForeignId('cancelled_by');
                }

                if (Schema::hasColumn('payroll_entries', 'cancelled_at')) {
                    $table->dropColumn('cancelled_at');
                }

                if (Schema::hasColumn('payroll_entries', 'is_cancelled')) {
                    $table->dropColumn('is_cancelled');
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table): void {
                if (Schema::hasColumn('payrolls', 'cancellation_reason')) {
                    $table->dropColumn('cancellation_reason');
                }

                if (Schema::hasColumn('payrolls', 'cancelled_by')) {
                    $table->dropConstrainedForeignId('cancelled_by');
                }

                if (Schema::hasColumn('payrolls', 'cancelled_at')) {
                    $table->dropColumn('cancelled_at');
                }
            });
        }
    }
};
