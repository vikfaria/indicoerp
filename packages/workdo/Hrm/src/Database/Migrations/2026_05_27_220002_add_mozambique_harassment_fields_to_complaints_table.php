<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('complaints')) {
            return;
        }

        Schema::table('complaints', function (Blueprint $table): void {
            if (!Schema::hasColumn('complaints', 'is_confidential')) {
                $table->boolean('is_confidential')->default(false)->after('status');
            }

            if (!Schema::hasColumn('complaints', 'is_harassment_report')) {
                $table->boolean('is_harassment_report')->default(false)->after('is_confidential');
                $table->index('is_harassment_report');
            }

            if (!Schema::hasColumn('complaints', 'confidential_channel')) {
                $table->string('confidential_channel', 60)->nullable()->after('is_harassment_report');
            }

            if (!Schema::hasColumn('complaints', 'confidentiality_level')) {
                $table->string('confidentiality_level', 30)->default('internal')->after('confidential_channel');
            }

            if (!Schema::hasColumn('complaints', 'investigation_started_at')) {
                $table->date('investigation_started_at')->nullable()->after('resolution_date');
            }

            if (!Schema::hasColumn('complaints', 'investigation_closed_at')) {
                $table->date('investigation_closed_at')->nullable()->after('investigation_started_at');
            }

            if (!Schema::hasColumn('complaints', 'handling_owner_id')) {
                $table->foreignId('handling_owner_id')->nullable()->after('resolved_by');
                $table->foreign('handling_owner_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('complaints')) {
            return;
        }

        Schema::table('complaints', function (Blueprint $table): void {
            if (Schema::hasColumn('complaints', 'handling_owner_id')) {
                $table->dropForeign(['handling_owner_id']);
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('complaints', 'is_confidential') ? 'is_confidential' : null,
                Schema::hasColumn('complaints', 'is_harassment_report') ? 'is_harassment_report' : null,
                Schema::hasColumn('complaints', 'confidential_channel') ? 'confidential_channel' : null,
                Schema::hasColumn('complaints', 'confidentiality_level') ? 'confidentiality_level' : null,
                Schema::hasColumn('complaints', 'investigation_started_at') ? 'investigation_started_at' : null,
                Schema::hasColumn('complaints', 'investigation_closed_at') ? 'investigation_closed_at' : null,
                Schema::hasColumn('complaints', 'handling_owner_id') ? 'handling_owner_id' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
