<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_applications')) {
            return;
        }

        Schema::table('leave_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('leave_applications', 'legal_reference_date')) {
                $table->date('legal_reference_date')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('leave_applications', 'compensated_days')) {
                $table->unsignedInteger('compensated_days')->default(0)->after('total_days');
            }
            if (!Schema::hasColumn('leave_applications', 'effective_rest_days')) {
                $table->unsignedInteger('effective_rest_days')->nullable()->after('compensated_days');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leave_applications')) {
            return;
        }

        Schema::table('leave_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_applications', 'legal_reference_date')) {
                $table->dropColumn('legal_reference_date');
            }
            if (Schema::hasColumn('leave_applications', 'compensated_days')) {
                $table->dropColumn('compensated_days');
            }
            if (Schema::hasColumn('leave_applications', 'effective_rest_days')) {
                $table->dropColumn('effective_rest_days');
            }
        });
    }
};

