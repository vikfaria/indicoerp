<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (!Schema::hasColumn('attendances', 'source_channel')) {
                $table->string('source_channel', 40)->nullable()->after('status');
                $table->index('source_channel');
            }

            if (!Schema::hasColumn('attendances', 'source_device_id')) {
                $table->string('source_device_id', 120)->nullable()->after('source_channel');
            }

            if (!Schema::hasColumn('attendances', 'source_device_label')) {
                $table->string('source_device_label', 160)->nullable()->after('source_device_id');
            }

            if (!Schema::hasColumn('attendances', 'source_reference')) {
                $table->string('source_reference', 160)->nullable()->after('source_device_label');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'source_channel')) {
                $table->dropIndex(['source_channel']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('attendances', 'source_channel') ? 'source_channel' : null,
                Schema::hasColumn('attendances', 'source_device_id') ? 'source_device_id' : null,
                Schema::hasColumn('attendances', 'source_device_label') ? 'source_device_label' : null,
                Schema::hasColumn('attendances', 'source_reference') ? 'source_reference' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
