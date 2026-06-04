<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payments', 'payment_purpose')) {
                $table->string('payment_purpose', 20)->default('settlement')->after('vendor_id');
                $table->index(['created_by', 'payment_purpose']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_payments', 'payment_purpose')) {
                $table->dropIndex(['created_by', 'payment_purpose']);
                $table->dropColumn('payment_purpose');
            }
        });
    }
};
