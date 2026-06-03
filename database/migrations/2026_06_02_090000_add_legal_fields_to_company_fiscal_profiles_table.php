<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_fiscal_profiles')) {
            return;
        }

        Schema::table('company_fiscal_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('company_fiscal_profiles', 'legal_name')) {
                $table->string('legal_name', 255)->nullable()->after('nuit');
            }

            if (!Schema::hasColumn('company_fiscal_profiles', 'taxpayer_type')) {
                $table->string('taxpayer_type', 80)->nullable()->after('entity_type');
            }

            if (!Schema::hasColumn('company_fiscal_profiles', 'state_of_certification')) {
                $table->string('state_of_certification', 50)->nullable()->after('taxpayer_type');
            }

            if (!Schema::hasColumn('company_fiscal_profiles', 'software_certificate_number')) {
                $table->string('software_certificate_number', 100)->nullable()->after('state_of_certification');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_fiscal_profiles')) {
            return;
        }

        Schema::table('company_fiscal_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('company_fiscal_profiles', 'software_certificate_number')) {
                $table->dropColumn('software_certificate_number');
            }

            if (Schema::hasColumn('company_fiscal_profiles', 'state_of_certification')) {
                $table->dropColumn('state_of_certification');
            }

            if (Schema::hasColumn('company_fiscal_profiles', 'taxpayer_type')) {
                $table->dropColumn('taxpayer_type');
            }

            if (Schema::hasColumn('company_fiscal_profiles', 'legal_name')) {
                $table->dropColumn('legal_name');
            }
        });
    }
};
