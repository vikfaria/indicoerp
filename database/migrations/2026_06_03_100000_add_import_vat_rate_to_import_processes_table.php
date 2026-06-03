<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('import_processes') || Schema::hasColumn('import_processes', 'import_vat_rate')) {
            return;
        }

        Schema::table('import_processes', function (Blueprint $table) {
            $table->decimal('import_vat_rate', 5, 2)
                ->nullable()
                ->after('customs_duty_rate')
                ->comment('Taxa de IVA de importação aplicada ao processo (%)');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('import_processes') || !Schema::hasColumn('import_processes', 'import_vat_rate')) {
            return;
        }

        Schema::table('import_processes', function (Blueprint $table) {
            $table->dropColumn('import_vat_rate');
        });
    }
};
