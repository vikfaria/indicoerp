<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('training_types')) {
            return;
        }

        Schema::table('training_types', function (Blueprint $table): void {
            if (!Schema::hasColumn('training_types', 'is_mandatory')) {
                $table->boolean('is_mandatory')->default(false)->after('description');
            }

            if (!Schema::hasColumn('training_types', 'compliance_code')) {
                $table->string('compliance_code', 50)->nullable()->after('is_mandatory');
            }

            if (!Schema::hasColumn('training_types', 'certificate_validity_days')) {
                $table->unsignedInteger('certificate_validity_days')->nullable()->after('compliance_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('training_types')) {
            return;
        }

        Schema::table('training_types', function (Blueprint $table): void {
            if (Schema::hasColumn('training_types', 'certificate_validity_days')) {
                $table->dropColumn('certificate_validity_days');
            }

            if (Schema::hasColumn('training_types', 'compliance_code')) {
                $table->dropColumn('compliance_code');
            }

            if (Schema::hasColumn('training_types', 'is_mandatory')) {
                $table->dropColumn('is_mandatory');
            }
        });
    }
};

