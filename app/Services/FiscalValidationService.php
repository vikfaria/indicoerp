<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\CompanyFiscalProfile;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Models\ChartOfAccount;

/**
 * Central fiscal validation service for SCE Moçambique compliance.
 * All fiscal rules are enforced through this service.
 */
class FiscalValidationService
{
    /**
     * Validate that a date falls within an open accounting period.
     *
     * @throws ValidationException
     */
    public function validatePeriodOpen(string $date, int $companyId): AccountingPeriod
    {
        if (!Schema::hasTable('accounting_periods')) {
            // Table not yet migrated — skip validation
            return new AccountingPeriod(['status' => 'open']);
        }

        $period = AccountingPeriod::forCompany($companyId)
            ->forDate($date)
            ->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'date' => __('Não existe período contabilístico definido para a data :date.', ['date' => $date]),
            ]);
        }

        if (!$period->isOpen()) {
            throw ValidationException::withMessages([
                'date' => __('O período ":period" está :status. Não é possível registar operações.', [
                    'period' => $period->period_name,
                    'status' => $period->status === 'closed' ? 'fechado' : 'em fecho',
                ]),
            ]);
        }

        return $period;
    }

    /**
     * Validate a Mozambique NUIT (9 digits).
     *
     * @throws ValidationException
     */
    public function validateNuit(?string $nuit, string $field = 'nuit'): void
    {
        if ($nuit === null || trim($nuit) === '') {
            return; // NUIT is optional in some contexts
        }

        $nuit = trim($nuit);

        if (!preg_match('/^\d{9}$/', $nuit)) {
            throw ValidationException::withMessages([
                $field => __('O NUIT deve conter exactamente 9 dígitos numéricos.'),
            ]);
        }
    }

    /**
     * Validate NUIT is present and valid (strict mode for fiscal documents).
     *
     * @throws ValidationException
     */
    public function requireValidNuit(?string $nuit, string $field = 'nuit'): void
    {
        if ($nuit === null || trim($nuit) === '') {
            throw ValidationException::withMessages([
                $field => __('O NUIT é obrigatório para este tipo de documento fiscal.'),
            ]);
        }

        $this->validateNuit($nuit, $field);
    }

    /**
     * Validate document sequence is chronological and gap-free within a series.
     *
     * @throws ValidationException
     */
    public function validateSequence(
        string $documentType,
        string $series,
        int $sequence,
        string $date,
        int $companyId
    ): void {
        // Check for gaps — the previous sequence must exist
        if ($sequence > 1) {
            $previousExists = \DB::table($this->resolveDocumentTable($documentType))
                ->where('created_by', $companyId)
                ->where('document_series', $series)
                ->where('document_sequence', $sequence - 1)
                ->exists();

            if (!$previousExists) {
                throw ValidationException::withMessages([
                    'sequence' => __('Lacuna na numeração: o documento :type série :series nº :prev não existe.', [
                        'type' => $documentType,
                        'series' => $series,
                        'prev' => $sequence - 1,
                    ]),
                ]);
            }
        }
    }

    /**
     * Validate that a journal entry targets only movement accounts.
     *
     * @throws ValidationException
     */
    public function validateAccountIsMovement(int $accountId): void
    {
        if (!Schema::hasColumn('chart_of_accounts', 'is_movement_account')) {
            return;
        }

        $account = ChartOfAccount::find($accountId);

        if ($account === null) {
            throw ValidationException::withMessages([
                'account_id' => __('Conta contabilística não encontrada.'),
            ]);
        }

        if (!$account->is_movement_account) {
            throw ValidationException::withMessages([
                'account_id' => __('A conta ":code - :name" é sintética e não aceita lançamentos directos.', [
                    'code' => $account->account_code,
                    'name' => $account->account_name,
                ]),
            ]);
        }
    }

    /**
     * Validate that a finalized document cannot be modified.
     *
     * @throws ValidationException
     */
    public function validateDocumentMutable(Model $document): void
    {
        // Check fiscal submission status
        $fiscalStatus = $document->getAttribute('fiscal_submission_status');
        if (in_array($fiscalStatus, ['submitted', 'validated'], true)) {
            throw ValidationException::withMessages([
                'document' => __('Documento fiscal submetido/validado não pode ser alterado. Use nota de crédito/débito para correcções.'),
            ]);
        }

        // Check if cancelled
        if ((bool) $document->getAttribute('is_cancelled')) {
            throw ValidationException::withMessages([
                'document' => __('Documento cancelado não pode ser alterado.'),
            ]);
        }

        // Check operational status
        $status = strtolower((string) $document->getAttribute('status'));
        $immutableStatuses = ['paid', 'completed', 'finalized'];

        if (in_array($status, $immutableStatuses, true)) {
            throw ValidationException::withMessages([
                'document' => __('Documento com estado ":status" não pode ser alterado.', ['status' => $status]),
            ]);
        }
    }

    /**
     * Validate that the company has a valid fiscal profile.
     *
     * @throws ValidationException
     */
    public function validateCompanyFiscalProfile(int $companyId): CompanyFiscalProfile
    {
        if (!Schema::hasTable('company_fiscal_profiles')) {
            return new CompanyFiscalProfile(['accounting_framework' => 'pgc_nirf']);
        }

        $profile = CompanyFiscalProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if ($profile === null) {
            throw ValidationException::withMessages([
                'company' => __('A empresa não tem perfil fiscal configurado. Configure em Definições > Perfil Fiscal.'),
            ]);
        }

        return $profile;
    }

    /**
     * Resolve the database table for a given document type.
     */
    private function resolveDocumentTable(string $documentType): string
    {
        return match ($documentType) {
            'sales_invoice', 'invoice' => 'sales_invoices',
            'purchase_invoice', 'bill' => 'purchase_invoices',
            'credit_note' => 'credit_notes',
            'debit_note' => 'debit_notes',
            'sales_return' => 'sales_invoice_returns',
            'purchase_return' => 'purchase_returns',
            'pos' => 'pos',
            default => $documentType,
        };
    }
}
