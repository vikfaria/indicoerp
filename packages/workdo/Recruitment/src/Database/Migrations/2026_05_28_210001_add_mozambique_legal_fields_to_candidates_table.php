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
            if (!Schema::hasColumn('candidates', 'nationality')) {
                $table->string('nationality')->nullable()->after('dob');
            }
            if (!Schema::hasColumn('candidates', 'identification_document_type')) {
                $table->string('identification_document_type')->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('candidates', 'identification_document_number')) {
                $table->string('identification_document_number')->nullable()->after('identification_document_type');
            }
            if (!Schema::hasColumn('candidates', 'nuit')) {
                $table->string('nuit', 32)->nullable()->after('identification_document_number');
            }
            if (!Schema::hasColumn('candidates', 'desired_professional_category')) {
                $table->string('desired_professional_category')->nullable()->after('nuit');
            }
            if (!Schema::hasColumn('candidates', 'is_regulated_profession')) {
                $table->boolean('is_regulated_profession')->default(false)->after('desired_professional_category');
            }
            if (!Schema::hasColumn('candidates', 'professional_license_type')) {
                $table->string('professional_license_type')->nullable()->after('is_regulated_profession');
            }
            if (!Schema::hasColumn('candidates', 'professional_license_number')) {
                $table->string('professional_license_number')->nullable()->after('professional_license_type');
            }
            if (!Schema::hasColumn('candidates', 'professional_license_expiry_date')) {
                $table->date('professional_license_expiry_date')->nullable()->after('professional_license_number');
            }
            if (!Schema::hasColumn('candidates', 'minor_work_authorization_path')) {
                $table->string('minor_work_authorization_path')->nullable()->after('professional_license_expiry_date');
            }
            if (!Schema::hasColumn('candidates', 'legal_exception_notes')) {
                $table->text('legal_exception_notes')->nullable()->after('minor_work_authorization_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            $columns = [
                'nationality',
                'identification_document_type',
                'identification_document_number',
                'nuit',
                'desired_professional_category',
                'is_regulated_profession',
                'professional_license_type',
                'professional_license_number',
                'professional_license_expiry_date',
                'minor_work_authorization_path',
                'legal_exception_notes',
            ];

            $existingColumns = array_values(array_filter($columns, static fn(string $column): bool => Schema::hasColumn('candidates', $column)));
            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

