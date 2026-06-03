<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CompanyFiscalProfile;
use App\Models\MzVatCode;
use RuntimeException;

/**
 * SAF-T (Standard Audit File for Tax) generator for Mozambique.
 * Produces XML output conforming to the Mozambican SAF-T schema.
 */
class SaftExportService
{
    private int $companyId;
    private string $startDate;
    private string $endDate;
    private string $fiscalYear;

    /**
     * Generate SAF-T XML for a company and period.
     */
    public function generate(int $companyId, string $startDate, string $endDate): string
    {
        $this->companyId = $companyId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->fiscalYear = date('Y', strtotime($startDate));

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('AuditFile');
        $xml->writeAttribute('xmlns', 'urn:OECD:StandardAuditFile-Tax:MZ_1.0');

        $this->writeHeader($xml);
        $this->writeMasterFiles($xml);
        $this->writeGeneralLedgerEntries($xml);
        $this->writeSourceDocuments($xml);

        $xml->endElement(); // AuditFile
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Export SAF-T to a file.
     */
    public function exportToFile(int $companyId, string $startDate, string $endDate, ?string $path = null): string
    {
        $xml = $this->generate($companyId, $startDate, $endDate);
        $this->validateXml($xml);

        if (!$path) {
            $path = storage_path("app/saft/SAFT-MZ-{$companyId}-{$this->fiscalYear}.xml");
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $xml);

        return $path;
    }

    public function validateGeneratedXml(string $xml): void
    {
        $this->validateXml($xml);
    }

    private function writeHeader(\XMLWriter $xml): void
    {
        $profile = CompanyFiscalProfile::where('company_id', $this->companyId)->first();
        $company = DB::table('users')->find($this->companyId);
        $companyName = $profile?->legal_name ?: ($company?->name ?? '');
        $softwareCertificateNumber = trim((string) ($profile?->software_certificate_number ?? ''));
        if ($softwareCertificateNumber === '') {
            $softwareCertificateNumber = '0';
        }

        $xml->startElement('Header');
        $xml->writeElement('AuditFileVersion', '1.0_01');
        $xml->writeElement('CompanyID', $profile?->nuit ?? '');
        $xml->writeElement('TaxRegistrationNumber', $profile?->nuit ?? '');
        $xml->writeElement('TaxAccountingBasis', 'C'); // Contabilidade
        $xml->writeElement('CompanyName', $companyName);
        $xml->writeElement('FiscalYear', $this->fiscalYear);
        $xml->writeElement('StartDate', $this->startDate);
        $xml->writeElement('EndDate', $this->endDate);
        $xml->writeElement('CurrencyCode', 'MZN');
        $xml->writeElement('DateCreated', now()->toDateString());
        $xml->writeElement('TaxEntity', 'Global');
        $xml->writeElement('ProductCompanyTaxID', '');
        $xml->writeElement('SoftwareCertificateNumber', $softwareCertificateNumber);
        $xml->writeElement('ProductID', 'SysGest ERP');
        $xml->writeElement('ProductVersion', '1.0');

        if ($profile) {
            $xml->startElement('CompanyAddress');
            $xml->writeElement('City', $profile->province ?? '');
            $xml->writeElement('Country', 'MZ');
            $xml->endElement();
        }

        $xml->endElement(); // Header
    }

    private function writeMasterFiles(\XMLWriter $xml): void
    {
        $xml->startElement('MasterFiles');

        // Chart of Accounts
        $this->writeChartOfAccounts($xml);

        // Customers
        $this->writeCustomers($xml);

        // Suppliers
        $this->writeSuppliers($xml);

        // Tax Table
        $this->writeTaxTable($xml);

        $xml->endElement(); // MasterFiles
    }

    private function writeChartOfAccounts(\XMLWriter $xml): void
    {
        $accounts = DB::table('chart_of_accounts')
            ->where('created_by', $this->companyId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        foreach ($accounts as $account) {
            $xml->startElement('GeneralLedgerAccounts');
            $xml->writeElement('AccountID', $account->account_code);
            $xml->writeElement('AccountDescription', $account->account_name);

            $grouping = $account->is_movement_account ? 'GM' : 'GA'; // Geral Movimento / Geral Acumulação
            $xml->writeElement('GroupingCategory', $grouping);
            $xml->writeElement('GroupingCode', $account->pgc_class ?? substr($account->account_code, 0, 1));

            $xml->writeElement('OpeningDebitBalance', number_format(max($account->opening_balance ?? 0, 0), 2, '.', ''));
            $xml->writeElement('OpeningCreditBalance', number_format(max(-($account->opening_balance ?? 0), 0), 2, '.', ''));

            $balances = DB::table('journal_entry_items as jei')
                ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
                ->where('jei.account_id', $account->id)
                ->where('je.status', 'posted')
                ->whereBetween('je.journal_date', [$this->startDate, $this->endDate])
                ->selectRaw('COALESCE(SUM(jei.debit_amount), 0) as debits, COALESCE(SUM(jei.credit_amount), 0) as credits')
                ->first();

            $closingBalance = ($account->opening_balance ?? 0) + ($balances->debits ?? 0) - ($balances->credits ?? 0);

            $xml->writeElement('ClosingDebitBalance', number_format(max($closingBalance, 0), 2, '.', ''));
            $xml->writeElement('ClosingCreditBalance', number_format(max(-$closingBalance, 0), 2, '.', ''));

            $xml->endElement();
        }
    }

    private function writeCustomers(\XMLWriter $xml): void
    {
        $customers = DB::table('customers')
            ->where('created_by', $this->companyId)
            ->get();

        foreach ($customers as $customer) {
            $xml->startElement('Customer');
            $xml->writeElement('CustomerID', (string) $customer->id);
            $xml->writeElement('CustomerTaxID', $customer->tax_number ?? '999999999');
            $xml->writeElement('CompanyName', $customer->company_name ?? $customer->contact_person_name ?? '');

            $xml->startElement('BillingAddress');
            $xml->writeElement('AddressDetail', $customer->billing_address ?? '');
            $xml->writeElement('City', '');
            $xml->writeElement('PostalCode', '');
            $xml->writeElement('Country', 'MZ');
            $xml->endElement();

            $xml->writeElement('SelfBillingIndicator', '0');
            $xml->endElement();
        }
    }

    private function writeSuppliers(\XMLWriter $xml): void
    {
        $vendors = DB::table('vendors')
            ->where('created_by', $this->companyId)
            ->get();

        foreach ($vendors as $vendor) {
            $xml->startElement('Supplier');
            $xml->writeElement('SupplierID', (string) $vendor->id);
            $xml->writeElement('SupplierTaxID', $vendor->tax_number ?? '999999999');
            $xml->writeElement('CompanyName', $vendor->company_name ?? '');

            $xml->startElement('BillingAddress');
            $xml->writeElement('AddressDetail', $vendor->billing_address ?? '');
            $xml->writeElement('City', '');
            $xml->writeElement('PostalCode', '');
            $xml->writeElement('Country', $vendor->fiscal_country ?? 'MZ');
            $xml->endElement();

            $xml->writeElement('SelfBillingIndicator', '0');
            $xml->endElement();
        }
    }

    private function writeTaxTable(\XMLWriter $xml): void
    {
        $vatCodes = MzVatCode::where('is_active', true)->get();

        foreach ($vatCodes as $vat) {
            $xml->startElement('TaxTableEntry');
            $xml->writeElement('TaxType', 'IVA');
            $xml->writeElement('TaxCountryRegion', 'MZ');
            $xml->writeElement('TaxCode', $vat->saft_tax_code ?? $vat->code);
            $xml->writeElement('Description', $vat->description);
            $xml->writeElement('TaxPercentage', number_format($vat->rate, 2, '.', ''));

            if ($vat->exemption_reason) {
                $xml->writeElement('TaxExemptionReason', $vat->exemption_reason);
            }

            $xml->endElement();
        }
    }

    private function writeGeneralLedgerEntries(\XMLWriter $xml): void
    {
        $xml->startElement('GeneralLedgerEntries');

        $entries = DB::table('journal_entries')
            ->where('created_by', $this->companyId)
            ->where('status', 'posted')
            ->whereBetween('journal_date', [$this->startDate, $this->endDate])
            ->orderBy('journal_date')
            ->orderBy('id')
            ->get();

        $xml->writeElement('NumberOfEntries', (string) $entries->count());
        $totalDebit = $entries->sum('total_debit');
        $totalCredit = $entries->sum('total_credit');
        $xml->writeElement('TotalDebit', number_format($totalDebit, 2, '.', ''));
        $xml->writeElement('TotalCredit', number_format($totalCredit, 2, '.', ''));

        foreach ($entries as $entry) {
            $xml->startElement('Journal');
            $xml->writeElement('JournalID', $entry->accounting_journal_id ?? '0');
            $xml->writeElement('Description', 'Diário');

            $xml->startElement('Transaction');
            $xml->writeElement('TransactionID', $entry->journal_number ?? (string) $entry->id);
            $xml->writeElement('Period', $entry->period_number ?? date('m', strtotime($entry->journal_date)));
            $xml->writeElement('TransactionDate', $entry->journal_date);
            $xml->writeElement('Description', $entry->description ?? '');
            $xml->writeElement('DocArchivalNumber', $entry->journal_number ?? '');

            $items = DB::table('journal_entry_items')
                ->where('journal_entry_id', $entry->id)
                ->get();

            foreach ($items as $item) {
                $account = DB::table('chart_of_accounts')->find($item->account_id);

                $xml->startElement('Lines');
                $xml->writeElement('RecordID', (string) $item->id);
                $xml->writeElement('AccountID', $account->account_code ?? '');
                $xml->writeElement('Description', $item->description ?? '');

                if ($item->debit_amount > 0) {
                    $xml->writeElement('DebitAmount', number_format($item->debit_amount, 2, '.', ''));
                }
                if ($item->credit_amount > 0) {
                    $xml->writeElement('CreditAmount', number_format($item->credit_amount, 2, '.', ''));
                }

                $xml->endElement(); // Lines
            }

            $xml->endElement(); // Transaction
            $xml->endElement(); // Journal
        }

        $xml->endElement(); // GeneralLedgerEntries
    }

    private function writeSourceDocuments(\XMLWriter $xml): void
    {
        $xml->startElement('SourceDocuments');

        // Sales Invoices
        $this->writeSalesInvoices($xml);
        // Purchase Invoices
        $this->writePurchaseInvoices($xml);

        $xml->endElement();
    }

    private function writeSalesInvoices(\XMLWriter $xml): void
    {
        $tableName = 'sales_invoices';
        if (!DB::getSchemaBuilder()->hasTable($tableName)) return;

        $invoices = DB::table($tableName)
            ->where('created_by', $this->companyId)
            ->whereBetween('invoice_date', [$this->startDate, $this->endDate])
            ->orderBy('invoice_date')
            ->get();

        if ($invoices->isEmpty()) return;

        $xml->startElement('SalesInvoices');
        $xml->writeElement('NumberOfEntries', (string) $invoices->count());
        $xml->writeElement('TotalDebit', '0.00');
        $xml->writeElement('TotalCredit', number_format($invoices->sum('total_amount'), 2, '.', ''));

        foreach ($invoices as $invoice) {
            $xml->startElement('Invoice');
            $xml->writeElement('InvoiceNo', $invoice->invoice_number ?? (string) $invoice->id);
            $xml->writeElement('InvoiceStatus', $this->mapInvoiceStatus($invoice->status ?? 'N'));
            $xml->writeElement('Hash', $invoice->fiscal_hash ?? '0');
            $xml->writeElement('HashControl', $invoice->fiscal_hash_control ?? '0');
            $xml->writeElement('InvoiceDate', $invoice->invoice_date ?? '');
            $xml->writeElement('InvoiceType', 'FT');
            $xml->writeElement('CustomerID', (string) ($invoice->customer_id ?? ''));

            // Line items
            $items = DB::table('sales_invoice_items')
                ->where('invoice_id', $invoice->id)
                ->get();

            foreach ($items as $item) {
                $xml->startElement('Line');
                $xml->writeElement('LineNumber', (string) $item->id);
                $xml->writeElement('ProductCode', (string) ($item->product_id ?? ''));
                $xml->writeElement('Quantity', number_format($item->quantity, 2, '.', ''));
                $xml->writeElement('UnitPrice', number_format($item->unit_price, 2, '.', ''));

                $lineTotal = ($item->quantity * $item->unit_price);
                $discount = $lineTotal * (($item->discount_percentage ?? 0) / 100);

                $xml->writeElement('CreditAmount', number_format($lineTotal - $discount, 2, '.', ''));

                if ($item->tax_percentage > 0) {
                    $xml->startElement('Tax');
                    $xml->writeElement('TaxType', 'IVA');
                    $xml->writeElement('TaxCountryRegion', 'MZ');
                    $xml->writeElement('TaxCode', 'NOR');
                    $xml->writeElement('TaxPercentage', number_format($item->tax_percentage, 2, '.', ''));
                    $xml->endElement();
                }

                $xml->endElement(); // Line
            }

            $xml->startElement('DocumentTotals');
            $xml->writeElement('TaxPayable', number_format($invoice->tax_amount ?? 0, 2, '.', ''));
            $xml->writeElement('NetTotal', number_format($invoice->subtotal ?? 0, 2, '.', ''));
            $xml->writeElement('GrossTotal', number_format($invoice->total_amount ?? 0, 2, '.', ''));
            $xml->endElement();

            $xml->endElement(); // Invoice
        }

        $xml->endElement(); // SalesInvoices
    }

    private function writePurchaseInvoices(\XMLWriter $xml): void
    {
        $tableName = 'purchase_invoices';
        if (!DB::getSchemaBuilder()->hasTable($tableName)) return;

        $invoices = DB::table($tableName)
            ->where('created_by', $this->companyId)
            ->whereBetween('invoice_date', [$this->startDate, $this->endDate])
            ->orderBy('invoice_date')
            ->get();

        if ($invoices->isEmpty()) return;

        // SAF-T doesn't have a dedicated PurchaseInvoices section in the MZ schema,
        // but we include them under a custom element for completeness
        $xml->startElement('PurchaseInvoices');
        $xml->writeElement('NumberOfEntries', (string) $invoices->count());
        $xml->writeElement('TotalDebit', number_format($invoices->sum('total_amount'), 2, '.', ''));
        $xml->writeElement('TotalCredit', '0.00');

        foreach ($invoices as $invoice) {
            $xml->startElement('Invoice');
            $xml->writeElement('InvoiceNo', $invoice->invoice_number ?? (string) $invoice->id);
            $xml->writeElement('InvoiceStatus', $this->mapInvoiceStatus($invoice->status ?? 'N'));
            $xml->writeElement('Hash', $invoice->fiscal_hash ?? '0');
            $xml->writeElement('InvoiceDate', $invoice->invoice_date ?? '');
            $xml->writeElement('SupplierID', (string) ($invoice->vendor_id ?? ''));

            $xml->startElement('DocumentTotals');
            $xml->writeElement('TaxPayable', number_format($invoice->tax_amount ?? 0, 2, '.', ''));
            $xml->writeElement('NetTotal', number_format($invoice->subtotal ?? 0, 2, '.', ''));
            $xml->writeElement('GrossTotal', number_format($invoice->total_amount ?? 0, 2, '.', ''));
            $xml->endElement();

            $xml->endElement(); // Invoice
        }

        $xml->endElement(); // PurchaseInvoices
    }

    /**
     * Map internal status to SAF-T status codes.
     */
    private function mapInvoiceStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'N',      // Normal
            'posted' => 'N',     // Normal
            'paid' => 'N',       // Normal
            'partial' => 'N',    // Normal
            'cancelled' => 'A',  // Anulado
            default => 'N',
        };
    }

    /**
     * Validate generated SAF-T XML:
     * 1) Always check if XML is well formed.
     * 2) Optionally validate against configured XSD.
     *
     * @throws RuntimeException
     */
    private function validateXml(string $xml): void
    {
        $requireXsd = (bool) config('sce.saft.require_xsd_validation', false);
        $xsdPath = (string) config('sce.saft.xsd_path', '');

        $previousState = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');

            if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                $errors = $this->collectLibxmlErrors();
                throw new RuntimeException(__('SAF-T inválido: XML mal formado. :errors', ['errors' => $errors]));
            }

            if (!$requireXsd) {
                return;
            }

            if ($xsdPath === '' || !is_file($xsdPath) || !is_readable($xsdPath)) {
                throw new RuntimeException(__('SAF-T XSD não encontrado. Configure SAFT_MZ_XSD_PATH para validação oficial.'));
            }

            libxml_clear_errors();
            if (!$dom->schemaValidate($xsdPath)) {
                $errors = $this->collectLibxmlErrors();
                throw new RuntimeException(__('SAF-T inválido contra o XSD oficial: :errors', ['errors' => $errors]));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }
    }

    private function collectLibxmlErrors(int $limit = 5): string
    {
        $errors = libxml_get_errors();
        if (empty($errors)) {
            return __('sem detalhes do parser XML');
        }

        return collect($errors)
            ->take($limit)
            ->map(function (\LibXMLError $error) {
                return trim($error->message) . " (linha {$error->line})";
            })
            ->implode('; ');
    }
}
