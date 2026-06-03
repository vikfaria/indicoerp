<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnsToCustomerPayments();
        $this->addColumnsToVendorPayments();
    }

    public function down(): void
    {
        $this->dropColumnsFromCustomerPayments();
        $this->dropColumnsFromVendorPayments();
    }

    private function addColumnsToCustomerPayments(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('customer_payments', 'gifim_alert_required')) {
                $table->boolean('gifim_alert_required')
                    ->default(false)
                    ->after('fx_compliance_reference');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_alert_category')) {
                $table->string('gifim_alert_category', 40)
                    ->nullable()
                    ->after('gifim_alert_required');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_alert_status')) {
                $table->string('gifim_alert_status', 30)
                    ->default('not_required')
                    ->after('gifim_alert_category');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_reference')) {
                $table->string('gifim_reference', 120)
                    ->nullable()
                    ->after('gifim_alert_status');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_reported_at')) {
                $table->timestamp('gifim_reported_at')
                    ->nullable()
                    ->after('gifim_reference');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_reported_by')) {
                $table->unsignedBigInteger('gifim_reported_by')
                    ->nullable()
                    ->after('gifim_reported_at');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_submitted_document')) {
                $table->string('gifim_submitted_document', 255)
                    ->nullable()
                    ->after('gifim_reported_by');
            }

            if (!Schema::hasColumn('customer_payments', 'gifim_justification')) {
                $table->string('gifim_justification', 255)
                    ->nullable()
                    ->after('gifim_submitted_document');
            }

            if (!Schema::hasColumn('customer_payments', 'high_value_approval_reference')) {
                $table->string('high_value_approval_reference', 120)
                    ->nullable()
                    ->after('gifim_justification');
            }
        });
    }

    private function addColumnsToVendorPayments(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payments', 'gifim_alert_required')) {
                $table->boolean('gifim_alert_required')
                    ->default(false)
                    ->after('fx_authorization_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_alert_category')) {
                $table->string('gifim_alert_category', 40)
                    ->nullable()
                    ->after('gifim_alert_required');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_alert_status')) {
                $table->string('gifim_alert_status', 30)
                    ->default('not_required')
                    ->after('gifim_alert_category');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_reference')) {
                $table->string('gifim_reference', 120)
                    ->nullable()
                    ->after('gifim_alert_status');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_reported_at')) {
                $table->timestamp('gifim_reported_at')
                    ->nullable()
                    ->after('gifim_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_reported_by')) {
                $table->unsignedBigInteger('gifim_reported_by')
                    ->nullable()
                    ->after('gifim_reported_at');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_submitted_document')) {
                $table->string('gifim_submitted_document', 255)
                    ->nullable()
                    ->after('gifim_reported_by');
            }

            if (!Schema::hasColumn('vendor_payments', 'gifim_justification')) {
                $table->string('gifim_justification', 255)
                    ->nullable()
                    ->after('gifim_submitted_document');
            }

            if (!Schema::hasColumn('vendor_payments', 'high_value_approval_reference')) {
                $table->string('high_value_approval_reference', 120)
                    ->nullable()
                    ->after('gifim_justification');
            }
        });
    }

    private function dropColumnsFromCustomerPayments(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table): void {
            $columns = [
                'high_value_approval_reference',
                'gifim_justification',
                'gifim_submitted_document',
                'gifim_reported_by',
                'gifim_reported_at',
                'gifim_reference',
                'gifim_alert_status',
                'gifim_alert_category',
                'gifim_alert_required',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customer_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function dropColumnsFromVendorPayments(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            $columns = [
                'high_value_approval_reference',
                'gifim_justification',
                'gifim_submitted_document',
                'gifim_reported_by',
                'gifim_reported_at',
                'gifim_reference',
                'gifim_alert_status',
                'gifim_alert_category',
                'gifim_alert_required',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
