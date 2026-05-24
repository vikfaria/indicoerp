<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pgc_account_mappings')) {
            Schema::create('pgc_account_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('legacy_account_code', 20)->comment('Old generic code e.g. 1100');
                $table->string('pgc_account_code', 20)->comment('New PGC-MZ code e.g. 211');
                $table->enum('status', ['pending', 'mapped', 'verified'])->default('pending');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['company_id', 'legacy_account_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pgc_account_mappings');
    }
};
