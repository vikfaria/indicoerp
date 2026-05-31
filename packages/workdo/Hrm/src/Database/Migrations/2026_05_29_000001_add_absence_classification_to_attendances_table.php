<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (!Schema::hasColumn('attendances', 'is_justified')) {
                $table->boolean('is_justified')->nullable()->after('status');
            }

            if (!Schema::hasColumn('attendances', 'absence_category')) {
                $table->string('absence_category', 60)->nullable()->after('is_justified');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'absence_category')) {
                $table->dropColumn('absence_category');
            }

            if (Schema::hasColumn('attendances', 'is_justified')) {
                $table->dropColumn('is_justified');
            }
        });
    }
};

