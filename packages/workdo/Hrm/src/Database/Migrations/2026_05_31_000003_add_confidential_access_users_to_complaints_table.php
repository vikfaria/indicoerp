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
            if (!Schema::hasColumn('complaints', 'confidential_access_user_ids')) {
                $table->json('confidential_access_user_ids')->nullable()->after('confidentiality_level');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('complaints')) {
            return;
        }

        Schema::table('complaints', function (Blueprint $table): void {
            if (Schema::hasColumn('complaints', 'confidential_access_user_ids')) {
                $table->dropColumn('confidential_access_user_ids');
            }
        });
    }
};
