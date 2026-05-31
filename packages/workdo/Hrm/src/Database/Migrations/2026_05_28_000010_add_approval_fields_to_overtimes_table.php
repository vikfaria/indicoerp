<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('overtimes')) {
            return;
        }

        $hasApprovalStatus = Schema::hasColumn('overtimes', 'approval_status');
        $hasApprovedBy = Schema::hasColumn('overtimes', 'approved_by');
        $hasApprovedAt = Schema::hasColumn('overtimes', 'approved_at');
        $hasRejectedBy = Schema::hasColumn('overtimes', 'rejected_by');
        $hasRejectedAt = Schema::hasColumn('overtimes', 'rejected_at');
        $hasRejectionReason = Schema::hasColumn('overtimes', 'rejection_reason');

        Schema::table('overtimes', function (Blueprint $table) use (
            $hasApprovalStatus,
            $hasApprovedBy,
            $hasApprovedAt,
            $hasRejectedBy,
            $hasRejectedAt,
            $hasRejectionReason
        ): void {
            if (!$hasApprovalStatus) {
                $table->string('approval_status', 20)->nullable()->after('status');
            }

            if (!$hasApprovedBy) {
                $table->foreignId('approved_by')->nullable()->after('approval_status');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }

            if (!$hasApprovedAt) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!$hasRejectedBy) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at');
                $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            }

            if (!$hasRejectedAt) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (!$hasRejectionReason) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        // Backfill legacy records to keep payroll behavior deterministic.
        \Illuminate\Support\Facades\DB::table('overtimes')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => \Illuminate\Support\Facades\DB::raw("CASE WHEN status = 'active' THEN 'approved' ELSE 'rejected' END"),
            ]);

        \Illuminate\Support\Facades\DB::table('overtimes')
            ->where('approval_status', 'approved')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => \Illuminate\Support\Facades\DB::raw('created_at'),
                'approved_by' => \Illuminate\Support\Facades\DB::raw('creator_id'),
            ]);

        \Illuminate\Support\Facades\DB::table('overtimes')
            ->where('approval_status', 'rejected')
            ->whereNull('rejected_at')
            ->update([
                'rejected_at' => \Illuminate\Support\Facades\DB::raw('updated_at'),
                'rejected_by' => \Illuminate\Support\Facades\DB::raw('creator_id'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('overtimes')) {
            return;
        }

        $hasApprovedBy = Schema::hasColumn('overtimes', 'approved_by');
        $hasRejectedBy = Schema::hasColumn('overtimes', 'rejected_by');
        $hasApprovalStatus = Schema::hasColumn('overtimes', 'approval_status');
        $hasApprovedAt = Schema::hasColumn('overtimes', 'approved_at');
        $hasRejectedAt = Schema::hasColumn('overtimes', 'rejected_at');
        $hasRejectionReason = Schema::hasColumn('overtimes', 'rejection_reason');

        Schema::table('overtimes', function (Blueprint $table) use (
            $hasApprovedBy,
            $hasRejectedBy,
            $hasApprovalStatus,
            $hasApprovedAt,
            $hasRejectedAt,
            $hasRejectionReason
        ): void {
            if ($hasApprovedBy) {
                $table->dropForeign(['approved_by']);
            }

            if ($hasRejectedBy) {
                $table->dropForeign(['rejected_by']);
            }

            $dropColumns = [];
            if ($hasApprovalStatus) { $dropColumns[] = 'approval_status'; }
            if ($hasApprovedBy) { $dropColumns[] = 'approved_by'; }
            if ($hasApprovedAt) { $dropColumns[] = 'approved_at'; }
            if ($hasRejectedBy) { $dropColumns[] = 'rejected_by'; }
            if ($hasRejectedAt) { $dropColumns[] = 'rejected_at'; }
            if ($hasRejectionReason) { $dropColumns[] = 'rejection_reason'; }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
