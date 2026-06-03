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
            if (!Schema::hasColumn('complaints', 'disciplinary_warning_id')) {
                $table->foreignId('disciplinary_warning_id')
                    ->nullable()
                    ->after('handling_owner_id')
                    ->constrained('warnings')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('complaints', 'disciplinary_case_opened_at')) {
                $table->date('disciplinary_case_opened_at')
                    ->nullable()
                    ->after('disciplinary_warning_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('complaints')) {
            return;
        }

        Schema::table('complaints', function (Blueprint $table): void {
            if (Schema::hasColumn('complaints', 'disciplinary_warning_id')) {
                $table->dropConstrainedForeignId('disciplinary_warning_id');
            }

            if (Schema::hasColumn('complaints', 'disciplinary_case_opened_at')) {
                $table->dropColumn('disciplinary_case_opened_at');
            }
        });
    }
};
