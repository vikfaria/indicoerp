<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_foreign_worker_profiles')) {
            Schema::table('employee_foreign_worker_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_foreign_worker_profiles', 'cessation_effective_date')) {
                    $table->date('cessation_effective_date')->nullable()->after('mozambique_entry_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_foreign_worker_profiles')) {
            Schema::table('employee_foreign_worker_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('employee_foreign_worker_profiles', 'cessation_effective_date')) {
                    $table->dropColumn('cessation_effective_date');
                }
            });
        }
    }
};
