<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendors')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `vendors` MODIFY `fiscal_country` VARCHAR(120) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendors')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `vendors` MODIFY `fiscal_country` VARCHAR(2) NULL DEFAULT 'MZ'");
    }
};
