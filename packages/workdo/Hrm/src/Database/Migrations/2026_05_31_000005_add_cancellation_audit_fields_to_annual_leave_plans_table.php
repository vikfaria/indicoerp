<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('annual_leave_plans')) {
            return;
        }

        Schema::table('annual_leave_plans', function (Blueprint $table): void {
            if (!Schema::hasColumn('annual_leave_plans', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('status');
                $table->index('is_cancelled');
            }

            if (!Schema::hasColumn('annual_leave_plans', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('is_cancelled');
            }

            if (!Schema::hasColumn('annual_leave_plans', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('annual_leave_plans', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('annual_leave_plans')) {
            return;
        }

        Schema::table('annual_leave_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('annual_leave_plans', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn('annual_leave_plans', 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }

            if (Schema::hasColumn('annual_leave_plans', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('annual_leave_plans', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }
};
