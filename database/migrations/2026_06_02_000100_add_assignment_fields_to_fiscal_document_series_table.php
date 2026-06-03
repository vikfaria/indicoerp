<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiscal_document_series')) {
            return;
        }

        Schema::table('fiscal_document_series', function (Blueprint $table): void {
            if (!Schema::hasColumn('fiscal_document_series', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')
                    ->nullable()
                    ->after('fiscal_document_type_id');
                $table->index('assigned_user_id', 'fds_assigned_user_idx');
            }

            if (!Schema::hasColumn('fiscal_document_series', 'terminal_code')) {
                $table->string('terminal_code', 50)
                    ->nullable()
                    ->after('assigned_user_id');
                $table->index('terminal_code', 'fds_terminal_code_idx');
            }

            if (!Schema::hasColumn('fiscal_document_series', 'fiscal_regime_code')) {
                $table->string('fiscal_regime_code', 50)
                    ->nullable()
                    ->after('terminal_code');
                $table->index('fiscal_regime_code', 'fds_fiscal_regime_code_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fiscal_document_series')) {
            return;
        }

        Schema::table('fiscal_document_series', function (Blueprint $table): void {
            if (Schema::hasColumn('fiscal_document_series', 'fiscal_regime_code')) {
                $table->dropIndex('fds_fiscal_regime_code_idx');
                $table->dropColumn('fiscal_regime_code');
            }

            if (Schema::hasColumn('fiscal_document_series', 'terminal_code')) {
                $table->dropIndex('fds_terminal_code_idx');
                $table->dropColumn('terminal_code');
            }

            if (Schema::hasColumn('fiscal_document_series', 'assigned_user_id')) {
                $table->dropIndex('fds_assigned_user_idx');
                $table->dropColumn('assigned_user_id');
            }
        });
    }
};

