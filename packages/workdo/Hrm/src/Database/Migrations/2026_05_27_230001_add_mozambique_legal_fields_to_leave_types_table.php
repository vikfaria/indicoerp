<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_types')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table): void {
            if (!Schema::hasColumn('leave_types', 'legal_code')) {
                $table->string('legal_code', 60)->nullable()->index()->after('name');
            }
            if (!Schema::hasColumn('leave_types', 'requires_supporting_document')) {
                $table->boolean('requires_supporting_document')->default(false)->after('is_paid');
            }
            if (!Schema::hasColumn('leave_types', 'must_be_consecutive')) {
                $table->boolean('must_be_consecutive')->default(false)->after('requires_supporting_document');
            }
            if (!Schema::hasColumn('leave_types', 'fixed_duration_days')) {
                $table->unsignedInteger('fixed_duration_days')->nullable()->after('must_be_consecutive');
            }
            if (!Schema::hasColumn('leave_types', 'min_advance_notice_days')) {
                $table->unsignedInteger('min_advance_notice_days')->nullable()->after('fixed_duration_days');
            }
            if (!Schema::hasColumn('leave_types', 'pre_event_start_window_days')) {
                $table->unsignedInteger('pre_event_start_window_days')->nullable()->after('min_advance_notice_days');
            }
            if (!Schema::hasColumn('leave_types', 'post_event_start_offset_days')) {
                $table->unsignedInteger('post_event_start_offset_days')->nullable()->after('pre_event_start_window_days');
            }
            if (!Schema::hasColumn('leave_types', 'allow_cash_out')) {
                $table->boolean('allow_cash_out')->default(false)->after('post_event_start_offset_days');
            }
            if (!Schema::hasColumn('leave_types', 'min_effective_rest_days')) {
                $table->unsignedInteger('min_effective_rest_days')->nullable()->after('allow_cash_out');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leave_types')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_types', 'legal_code')) {
                $table->dropIndex('leave_types_legal_code_index');
                $table->dropColumn('legal_code');
            }
            if (Schema::hasColumn('leave_types', 'requires_supporting_document')) {
                $table->dropColumn('requires_supporting_document');
            }
            if (Schema::hasColumn('leave_types', 'must_be_consecutive')) {
                $table->dropColumn('must_be_consecutive');
            }
            if (Schema::hasColumn('leave_types', 'fixed_duration_days')) {
                $table->dropColumn('fixed_duration_days');
            }
            if (Schema::hasColumn('leave_types', 'min_advance_notice_days')) {
                $table->dropColumn('min_advance_notice_days');
            }
            if (Schema::hasColumn('leave_types', 'pre_event_start_window_days')) {
                $table->dropColumn('pre_event_start_window_days');
            }
            if (Schema::hasColumn('leave_types', 'post_event_start_offset_days')) {
                $table->dropColumn('post_event_start_offset_days');
            }
            if (Schema::hasColumn('leave_types', 'allow_cash_out')) {
                $table->dropColumn('allow_cash_out');
            }
            if (Schema::hasColumn('leave_types', 'min_effective_rest_days')) {
                $table->dropColumn('min_effective_rest_days');
            }
        });
    }
};

