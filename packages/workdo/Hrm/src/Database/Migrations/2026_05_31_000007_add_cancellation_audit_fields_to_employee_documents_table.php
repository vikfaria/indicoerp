<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_documents')) {
            return;
        }

        Schema::table('employee_documents', function (Blueprint $table): void {
            if (!Schema::hasColumn('employee_documents', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('file_path');
                $table->index('is_cancelled');
            }

            if (!Schema::hasColumn('employee_documents', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('is_cancelled');
            }

            if (!Schema::hasColumn('employee_documents', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('employee_documents', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_documents')) {
            return;
        }

        Schema::table('employee_documents', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_documents', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn('employee_documents', 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }

            if (Schema::hasColumn('employee_documents', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('employee_documents', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }
};
