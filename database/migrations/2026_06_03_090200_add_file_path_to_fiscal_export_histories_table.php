<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiscal_export_histories')) {
            return;
        }

        Schema::table('fiscal_export_histories', function (Blueprint $table): void {
            if (!Schema::hasColumn('fiscal_export_histories', 'file_path')) {
                $table->string('file_path', 255)->nullable()->after('file_hash')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fiscal_export_histories')) {
            return;
        }

        Schema::table('fiscal_export_histories', function (Blueprint $table): void {
            if (Schema::hasColumn('fiscal_export_histories', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
