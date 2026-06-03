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
            if (!Schema::hasColumn('customer_payments', 'approval_required')) {
                $table->boolean('approval_required')->default(false)->after('status');
            }

            if (!Schema::hasColumn('customer_payments', 'approval_status')) {
                $table->string('approval_status', 30)->default('not_required')->after('approval_required');
            }

            if (!Schema::hasColumn('customer_payments', 'approval_risk_flags')) {
                $table->json('approval_risk_flags')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('customer_payments', 'approval_requested_at')) {
                $table->timestamp('approval_requested_at')->nullable()->after('approval_risk_flags');
            }

            if (!Schema::hasColumn('customer_payments', 'approval_reference')) {
                $table->string('approval_reference', 120)->nullable()->after('approval_requested_at');
            }

            if (!Schema::hasColumn('customer_payments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_reference');
            }

            if (!Schema::hasColumn('customer_payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('customer_payments', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('customer_payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('customer_payments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    private function addColumnsToVendorPayments(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            return;
        }

        Schema::table('vendor_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payments', 'approval_required')) {
                $table->boolean('approval_required')->default(false)->after('status');
            }

            if (!Schema::hasColumn('vendor_payments', 'approval_status')) {
                $table->string('approval_status', 30)->default('not_required')->after('approval_required');
            }

            if (!Schema::hasColumn('vendor_payments', 'approval_risk_flags')) {
                $table->json('approval_risk_flags')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('vendor_payments', 'approval_requested_at')) {
                $table->timestamp('approval_requested_at')->nullable()->after('approval_risk_flags');
            }

            if (!Schema::hasColumn('vendor_payments', 'approval_reference')) {
                $table->string('approval_reference', 120)->nullable()->after('approval_requested_at');
            }

            if (!Schema::hasColumn('vendor_payments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_reference');
            }

            if (!Schema::hasColumn('vendor_payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('vendor_payments', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('vendor_payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('vendor_payments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
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
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'approved_by',
                'approved_at',
                'approval_reference',
                'approval_requested_at',
                'approval_risk_flags',
                'approval_status',
                'approval_required',
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
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'approved_by',
                'approved_at',
                'approval_reference',
                'approval_requested_at',
                'approval_risk_flags',
                'approval_status',
                'approval_required',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
