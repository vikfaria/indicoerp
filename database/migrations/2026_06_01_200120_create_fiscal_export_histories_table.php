<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fiscal_export_histories')) {
            return;
        }

        Schema::create('fiscal_export_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('export_type', 60)->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable()->index();
            $table->string('file_name', 255)->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('status', 40)->default('generated')->index();
            $table->string('submission_channel', 40)->nullable();
            $table->string('submission_reference', 120)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_export_histories');
    }
};
