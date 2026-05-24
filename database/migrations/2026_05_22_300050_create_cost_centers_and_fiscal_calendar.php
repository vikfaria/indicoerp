<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cost_centers')) {
            Schema::create('cost_centers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('code', 20);
                $table->string('name');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('parent_id')->references('id')->on('cost_centers')->onDelete('set null');
                $table->unique(['company_id', 'code']);
            });
        }

        // Add cost_center to journal_entry_items
        if (Schema::hasTable('journal_entry_items')) {
            Schema::table('journal_entry_items', function (Blueprint $table) {
                if (!Schema::hasColumn('journal_entry_items', 'cost_center_id')) {
                    $table->unsignedBigInteger('cost_center_id')->nullable()->after('account_id');
                    $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('set null');
                }
            });
        }

        if (!Schema::hasTable('fiscal_calendar_events')) {
            Schema::create('fiscal_calendar_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('code', 30);
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('obligation_type', ['vat', 'irpc', 'irps', 'inss', 'withholding', 'saft', 'annual_accounts', 'other']);
                $table->date('due_date');
                $table->string('reference_period', 7)->nullable()->comment('YYYY-MM');
                $table->enum('status', ['pending', 'completed', 'overdue', 'not_applicable'])->default('pending');
                $table->date('completed_date')->nullable();
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['company_id', 'due_date', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('journal_entry_items') && Schema::hasColumn('journal_entry_items', 'cost_center_id')) {
            Schema::table('journal_entry_items', function (Blueprint $table) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            });
        }
        Schema::dropIfExists('fiscal_calendar_events');
        Schema::dropIfExists('cost_centers');
    }
};
