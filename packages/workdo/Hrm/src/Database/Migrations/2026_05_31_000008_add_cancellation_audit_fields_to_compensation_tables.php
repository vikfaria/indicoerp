<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['allowances', 'deductions', 'loans', 'overtimes'];

        foreach ($tables as $tableName) {
            $this->addCancellationColumns($tableName);
        }
    }

    public function down(): void
    {
        $tables = ['allowances', 'deductions', 'loans', 'overtimes'];

        foreach ($tables as $tableName) {
            $this->dropCancellationColumns($tableName);
        }
    }

    private function addCancellationColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (!Schema::hasColumn($tableName, 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('created_by');
                $table->index('is_cancelled');
            }

            if (!Schema::hasColumn($tableName, 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('is_cancelled');
            }

            if (!Schema::hasColumn($tableName, 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn($tableName, 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });
    }

    private function dropCancellationColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (Schema::hasColumn($tableName, 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn($tableName, 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }

            if (Schema::hasColumn($tableName, 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn($tableName, 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }
};
