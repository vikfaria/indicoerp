<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('terminations')) {
            return;
        }

        Schema::table('terminations', function (Blueprint $table): void {
            if (!Schema::hasColumn('terminations', 'offboarding_letter_delivered_at')) {
                $table->date('offboarding_letter_delivered_at')->nullable()->after('termination_date');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_assets_returned_at')) {
                $table->date('offboarding_assets_returned_at')->nullable()->after('offboarding_letter_delivered_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_access_revoked_at')) {
                $table->date('offboarding_access_revoked_at')->nullable()->after('offboarding_assets_returned_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_final_payment_at')) {
                $table->date('offboarding_final_payment_at')->nullable()->after('offboarding_access_revoked_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_certificate_issued_at')) {
                $table->date('offboarding_certificate_issued_at')->nullable()->after('offboarding_final_payment_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_inss_notified_at')) {
                $table->date('offboarding_inss_notified_at')->nullable()->after('offboarding_certificate_issued_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_migration_notified_at')) {
                $table->date('offboarding_migration_notified_at')->nullable()->after('offboarding_inss_notified_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_archive_completed_at')) {
                $table->date('offboarding_archive_completed_at')->nullable()->after('offboarding_migration_notified_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_completed_at')) {
                $table->date('offboarding_completed_at')->nullable()->after('offboarding_archive_completed_at');
                $table->index('offboarding_completed_at');
            }

            if (!Schema::hasColumn('terminations', 'offboarding_notes')) {
                $table->text('offboarding_notes')->nullable()->after('offboarding_completed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('terminations')) {
            return;
        }

        Schema::table('terminations', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('terminations', 'offboarding_letter_delivered_at') ? 'offboarding_letter_delivered_at' : null,
                Schema::hasColumn('terminations', 'offboarding_assets_returned_at') ? 'offboarding_assets_returned_at' : null,
                Schema::hasColumn('terminations', 'offboarding_access_revoked_at') ? 'offboarding_access_revoked_at' : null,
                Schema::hasColumn('terminations', 'offboarding_final_payment_at') ? 'offboarding_final_payment_at' : null,
                Schema::hasColumn('terminations', 'offboarding_certificate_issued_at') ? 'offboarding_certificate_issued_at' : null,
                Schema::hasColumn('terminations', 'offboarding_inss_notified_at') ? 'offboarding_inss_notified_at' : null,
                Schema::hasColumn('terminations', 'offboarding_migration_notified_at') ? 'offboarding_migration_notified_at' : null,
                Schema::hasColumn('terminations', 'offboarding_archive_completed_at') ? 'offboarding_archive_completed_at' : null,
                Schema::hasColumn('terminations', 'offboarding_completed_at') ? 'offboarding_completed_at' : null,
                Schema::hasColumn('terminations', 'offboarding_notes') ? 'offboarding_notes' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
