<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos')) {
            return;
        }

        Schema::table('pos', function (Blueprint $table): void {
            if (!Schema::hasColumn('pos', 'document_type')) {
                $table->string('document_type', 20)->nullable()->after('sale_number');
            }

            if (!Schema::hasColumn('pos', 'document_series')) {
                $table->string('document_series', 30)->nullable()->after('document_type');
            }

            if (!Schema::hasColumn('pos', 'document_sequence')) {
                $table->unsignedInteger('document_sequence')->nullable()->after('document_series');
            }

            if (!Schema::hasColumn('pos', 'establishment_id')) {
                $table->unsignedBigInteger('establishment_id')->nullable()->index()->after('warehouse_id');
            }

            if (!Schema::hasColumn('pos', 'fiscal_submission_status')) {
                $table->enum('fiscal_submission_status', ['pending', 'submitted', 'validated', 'rejected', 'not_required'])
                    ->default('pending')
                    ->index()
                    ->after('status');
            }

            if (!Schema::hasColumn('pos', 'fiscal_submission_reference')) {
                $table->string('fiscal_submission_reference', 120)->nullable()->after('fiscal_submission_status');
            }

            if (!Schema::hasColumn('pos', 'fiscal_submitted_at')) {
                $table->timestamp('fiscal_submitted_at')->nullable()->after('fiscal_submission_reference');
            }

            if (!Schema::hasColumn('pos', 'fiscal_validated_at')) {
                $table->timestamp('fiscal_validated_at')->nullable()->after('fiscal_submitted_at');
            }

            if (!Schema::hasColumn('pos', 'fiscal_validation_message')) {
                $table->string('fiscal_validation_message', 255)->nullable()->after('fiscal_validated_at');
            }

            if (!Schema::hasColumn('pos', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->index()->after('fiscal_validation_message');
            }

            if (!Schema::hasColumn('pos', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('is_cancelled');
            }

            if (!Schema::hasColumn('pos', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->index()->after('cancelled_at');
            }

            if (!Schema::hasColumn('pos', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('pos', 'cancellation_reference')) {
                $table->string('cancellation_reference', 120)->nullable()->after('cancellation_reason');
            }

            if (!Schema::hasColumn('pos', 'rectification_reference')) {
                $table->string('rectification_reference', 120)->nullable()->after('cancellation_reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pos')) {
            return;
        }

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

        Schema::table('pos', function (Blueprint $table) use ($columns): void {
            $dropColumns = array_values(array_filter($columns, fn($column) => Schema::hasColumn('pos', $column)));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
