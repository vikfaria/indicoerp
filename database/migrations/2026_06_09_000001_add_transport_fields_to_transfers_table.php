<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table): void {
                if (! Schema::hasColumn('transfers', 'carrier_name')) {
                    $table->string('carrier_name')->nullable()->after('date');
                }

                if (! Schema::hasColumn('transfers', 'vehicle_plate')) {
                    $table->string('vehicle_plate', 64)->nullable()->after('carrier_name');
                }

                if (! Schema::hasColumn('transfers', 'driver_name')) {
                    $table->string('driver_name')->nullable()->after('vehicle_plate');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table): void {
                if (Schema::hasColumn('transfers', 'driver_name')) {
                    $table->dropColumn('driver_name');
                }

                if (Schema::hasColumn('transfers', 'vehicle_plate')) {
                    $table->dropColumn('vehicle_plate');
                }

                if (Schema::hasColumn('transfers', 'carrier_name')) {
                    $table->dropColumn('carrier_name');
                }
            });
        }
    }
};
