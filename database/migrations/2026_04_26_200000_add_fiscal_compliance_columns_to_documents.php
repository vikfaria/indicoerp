<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'sales_invoices',
        'purchase_invoices',
        'sales_proposals',
        'sales_invoice_returns',
        'purchase_returns',
        'credit_notes',
        'debit_notes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'document_type')) {
                    $table->string('document_type', 20)->nullable();
                }

                if (!Schema::hasColumn($tableName, 'document_series')) {
                    $table->string('document_series', 30)->nullable();
                }

                if (!Schema::hasColumn($tableName, 'document_sequence')) {
                    $table->unsignedInteger('document_sequence')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'establishment_id')) {
                    $table->unsignedBigInteger('establishment_id')->nullable()->index();
                }

                if (!Schema::hasColumn($tableName, 'fiscal_submission_status')) {
                    $table->enum('fiscal_submission_status', ['pending', 'submitted', 'validated', 'rejected', 'not_required'])->default('pending')->index();
                }

                if (!Schema::hasColumn($tableName, 'fiscal_submission_reference')) {
                    $table->string('fiscal_submission_reference', 120)->nullable();
                }

                if (!Schema::hasColumn($tableName, 'fiscal_submitted_at')) {
                    $table->timestamp('fiscal_submitted_at')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'fiscal_validated_at')) {
                    $table->timestamp('fiscal_validated_at')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'fiscal_validation_message')) {
                    $table->string('fiscal_validation_message', 255)->nullable();
                }

                if (!Schema::hasColumn($tableName, 'is_cancelled')) {
                    $table->boolean('is_cancelled')->default(false)->index();
                }

                if (!Schema::hasColumn($tableName, 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->index();
                }

                if (!Schema::hasColumn($tableName, 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'cancellation_reference')) {
                    $table->string('cancellation_reference', 120)->nullable();
                }

                if (!Schema::hasColumn($tableName, 'rectification_reference')) {
                    $table->string('rectification_reference', 120)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'document_type',
            'document_series',
            'document_sequence',
            'establishment_id',
            'fiscal_submission_status',
            'fiscal_submission_reference',
            'fiscal_submitted_at',
            'fiscal_validated_at',
            'fiscal_validation_message',
            'is_cancelled',
            'cancelled_at',
            'cancelled_by',
            'cancellation_reason',
            'cancellation_reference',
            'rectification_reference',
        ];

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName): void {
                $dropColumns = [];

                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $dropColumns[] = $column;
                    }
                }

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
};
