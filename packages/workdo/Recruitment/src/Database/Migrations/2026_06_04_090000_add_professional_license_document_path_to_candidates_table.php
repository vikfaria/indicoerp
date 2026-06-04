<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            if (!Schema::hasColumn('candidates', 'professional_license_document_path')) {
                $table->string('professional_license_document_path')->nullable()->after('professional_license_expiry_date');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            if (Schema::hasColumn('candidates', 'professional_license_document_path')) {
                $table->dropColumn('professional_license_document_path');
            }
        });
    }
};
