import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Download, Printer, X } from 'lucide-react';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
} from '@/utils/helpers';
import {
    buildCommercialDocumentPdfOptions,
    buildPartyCityLine,
    buildPartyCountryLine,
    COMMERCIAL_DOCUMENT_CONTAINER_CLASS,
    CommercialDocumentTemplate,
    formatDocumentMoney,
    formatDocumentQuantity,
    moneyToPortugueseWords,
} from '@/components/documents/commercial-document-template';

interface CustomerPaymentReceipt {
    id: number;
    payment_number: string;
    payment_date: string;
    payment_method: string;
    payment_amount: number | string;
    amount_mzn?: number | string | null;
    currency_code?: string | null;
    exchange_rate?: number | string | null;
    foreign_amount?: number | string | null;
    reference_number?: string | null;
    mobile_money_provider?: string | null;
    mobile_money_number?: string | null;
    notes?: string | null;
    status: string;
    approval_required?: boolean;
    approval_status?: string | null;
    approval_reference?: string | null;
    rejection_reason?: string | null;
    created_at: string;
    customer?: {
        name?: string;
        email?: string;
    };
    bank_account?: {
        account_name?: string;
        account_number?: string;
        bank_name?: string;
        branch_name?: string | null;
        branch?: {
            branch_name?: string;
        } | null;
    };
    branch?: {
        branch_name?: string;
    } | null;
    allocations?: Array<{
        id: number;
        invoice_id: number;
        allocated_amount: number | string;
        invoice?: {
            invoice_number?: string;
            invoice_date?: string;
            total_amount?: number | string;
        } | null;
    }>;
    credit_note_applications?: Array<{
        id: number;
        credit_note_id: number;
        applied_amount: number | string;
        application_date?: string;
        credit_note?: {
            credit_note_number?: string;
            credit_note_date?: string;
            total_amount?: number | string;
        } | null;
    }>;
}

interface CustomerProfile {
    company_name?: string | null;
    tax_number?: string | null;
    contact_person_email?: string | null;
    contact_person_mobile?: string | null;
    billing_address?: {
        name?: string | null;
        address_line_1?: string | null;
        address_line_2?: string | null;
        address?: string | null;
        city?: string | null;
        state?: string | null;
        zip_code?: string | null;
        country?: string | null;
    } | null;
}

interface PrintProps {
    customerPayment: CustomerPaymentReceipt & Record<string, any>;
    customerProfile?: CustomerProfile | null;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const sanitizeFilename = (value: string): string => value.replace(/[^\w.-]+/g, '-');

const paymentMethodLabel = (method?: string, t?: (value: string) => string): string => {
    const translate = t || ((value: string) => value);
    const labels: Record<string, string> = {
        bank_transfer: translate('Bank Transfer'),
        cash: translate('Cash'),
        cheque: translate('Cheque'),
        card: translate('Card'),
        mobile_money: translate('Mobile Money'),
        other: translate('Other'),
    };

    return labels[method || ''] || translate('Bank Transfer');
};

const mobileProviderLabel = (provider?: string | null): string => {
    const labels: Record<string, string> = {
        mpesa: 'M-Pesa',
        emola: 'e-Mola',
        mkesh: 'mKesh',
    };

    return labels[provider || ''] || '-';
};

const paymentStatusLabel = (status?: string): string => {
    const labels: Record<string, string> = {
        cleared: 'Pago',
        pending: 'Pendente',
        cancelled: 'Cancelado',
    };

    return labels[status || ''] || 'Pendente';
};

const joinParts = (...parts: Array<string | null | undefined>): string => parts.filter(Boolean).join(', ');

const formatAddress = (address?: CustomerProfile['billing_address']): string => {
    if (!address) {
        return '';
    }

    return joinParts(
        address.address_line_1 || address.address || null,
        address.address_line_2 || null,
    );
};

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { customerPayment, customerProfile } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const [isPrinting, setIsPrinting] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const payment = customerPayment as CustomerPaymentReceipt;
    const issuer = resolveDocumentIssuer(payment as Record<string, any>, page.props);
    const issuerTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const documentNumber = payment.payment_number || `CP-${payment.id}`;
    const amount = toNumber(payment.amount_mzn ?? payment.payment_amount);
    const allocationsTotal = (payment.allocations || []).reduce((sum, allocation) => sum + toNumber(allocation.allocated_amount), 0);
    const creditNotesTotal = (payment.credit_note_applications || []).reduce((sum, application) => sum + toNumber(application.applied_amount), 0);
    const residualBalance = Math.max(amount - (allocationsTotal - creditNotesTotal), 0);
    const printOverlayVisible = isDownloading || isPrinting;
    const customerBilling = customerProfile?.billing_address || null;
    const customerName = customerProfile?.company_name || payment.customer?.name || '-';
    const recipientEmail = customerProfile?.contact_person_email || payment.customer?.email || null;
    const recipientTaxNumber = customerProfile?.tax_number || undefined;
    const recipientTaxLabel = recipientTaxNumber ? 'NUIT' : undefined;
    const recipientAddress = formatAddress(customerBilling);
    const recipientCityLine = customerBilling
        ? joinParts(customerBilling.city, customerBilling.state, customerBilling.zip_code)
        : '';
    const recipientCountryLine = customerBilling?.country || '';
    const authUser = (page.props as any).auth?.user;
    const query = new URLSearchParams(window.location.search);

    useEffect(() => {
        if (query.get('download') === 'pdf') {
            void downloadPDF();
            return;
        }

        if (query.get('print') === '1') {
            setIsPrinting(true);
            const timer = window.setTimeout(() => {
                window.print();
                setIsPrinting(false);
            }, 350);

            return () => window.clearTimeout(timer);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector(`.${COMMERCIAL_DOCUMENT_CONTAINER_CLASS}`);
        if (printContent) {
            try {
                await saveElementAsPdf(
                    printContent as HTMLElement,
                    buildCommercialDocumentPdfOptions(`recibo-${sanitizeFilename(documentNumber)}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const handlePrint = () => {
        window.print();
    };

    const allocationLines = (payment.allocations || []).map((allocation) => ({
        reference: allocation.invoice?.invoice_number || `FT-${allocation.invoice_id}`,
        description: (
            <>
                <div>Liquidação de factura</div>
                {allocation.invoice?.invoice_number && (
                    <div className="text-[10px] font-normal text-slate-500">
                        {allocation.invoice.invoice_number}
                    </div>
                )}
            </>
        ),
        unit: 'DOC',
        quantity: formatDocumentQuantity(1),
        unitPrice: formatDocumentMoney(allocation.allocated_amount, settings),
        discount: '-',
        netPrice: formatDocumentMoney(allocation.allocated_amount, settings),
        tax: '-',
        taxAmount: '-',
        total: formatDocumentMoney(allocation.allocated_amount, settings),
    }));

    const creditNoteLines = (payment.credit_note_applications || []).map((application) => ({
        reference: application.credit_note?.credit_note_number || `NC-${application.credit_note_id}`,
        description: (
            <>
                <div>Aplicação de nota de crédito</div>
                {application.credit_note?.credit_note_number && (
                    <div className="text-[10px] font-normal text-slate-500">
                        {application.credit_note.credit_note_number}
                    </div>
                )}
            </>
        ),
        unit: 'DOC',
        quantity: formatDocumentQuantity(1),
        unitPrice: formatDocumentMoney(application.applied_amount, settings),
        discount: '-',
        netPrice: formatDocumentMoney(application.applied_amount, settings),
        tax: '-',
        taxAmount: '-',
        total: formatDocumentMoney(application.applied_amount, settings),
    }));

    const lines = [...allocationLines, ...creditNoteLines];

    if (lines.length === 0) {
        lines.push({
            reference: documentNumber,
            description: (
                <>
                    <div>Recibo do pagamento de cliente</div>
                    <div className="text-[10px] font-normal text-slate-500">
                        {paymentMethodLabel(payment.payment_method, t)}
                    </div>
                </>
            ),
            unit: 'DOC',
            quantity: formatDocumentQuantity(1),
            unitPrice: formatDocumentMoney(amount, settings),
            discount: '-',
            netPrice: formatDocumentMoney(amount, settings),
            tax: '-',
            taxAmount: '-',
            total: formatDocumentMoney(amount, settings),
        });
    }

    const observations = [
        payment.notes,
        payment.reference_number ? `Referência: ${payment.reference_number}` : null,
        `Método de pagamento: ${paymentMethodLabel(payment.payment_method, t)}.`,
        payment.payment_method === 'mobile_money' && payment.mobile_money_provider
            ? `Operador móvel: ${mobileProviderLabel(payment.mobile_money_provider)}.`
            : null,
        payment.payment_method === 'mobile_money' && payment.mobile_money_number
            ? `Número móvel: ${payment.mobile_money_number}.`
            : null,
    ].filter(Boolean).join('\n') || 'Recibo financeiro emitido ao cliente.';

    const paymentApprovalLabel = !payment.approval_required
        ? 'Aprovação não requerida'
        : payment.approval_status === 'approved'
            ? 'Aprovado'
            : payment.approval_status === 'rejected'
                ? 'Rejeitado'
                : 'Aprovação pendente';

    const paymentStatusTone = payment.status === 'cleared'
        ? 'success'
        : payment.status === 'cancelled'
            ? 'danger'
            : 'warning';

    const approvalTone = !payment.approval_required
        ? 'info'
        : payment.approval_status === 'approved'
            ? 'success'
            : payment.approval_status === 'rejected'
                ? 'danger'
                : 'warning';

    return (
        <div className="min-h-screen bg-slate-100 py-6 print:bg-white print:py-0">
            <Head title={`Recibo #${documentNumber}`} />

            {printOverlayVisible && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="rounded-lg bg-white px-5 py-4 shadow-xl">
                        <div className="flex items-center gap-3">
                            <div className="h-5 w-5 animate-spin rounded-full border-b-2 border-emerald-600" />
                            <p className="text-sm font-semibold text-slate-700">
                                {t('Generating PDF...')}
                            </p>
                        </div>
                    </div>
                </div>
            )}

            <div className="mx-auto mb-4 flex w-full max-w-[210mm] justify-end gap-2 px-4 print:hidden">
                <Button variant="outline" size="sm" onClick={downloadPDF}>
                    <Download className="mr-2 h-4 w-4" />
                    {t('Download PDF')}
                </Button>
                <Button variant="outline" size="sm" onClick={handlePrint}>
                    <Printer className="mr-2 h-4 w-4" />
                    {t('Print')}
                </Button>
                <Button variant="outline" size="sm" onClick={() => window.close()}>
                    <X className="mr-2 h-4 w-4" />
                    {t('Close')}
                </Button>
            </div>

            <CommercialDocumentTemplate
                title="PAGAMENTO DE CLIENTE"
                subtitle="Recibo financeiro emitido após a receção do pagamento"
                documentLabel="Recibo"
                documentNumber={documentNumber}
                copyLabel={payment.status === 'cleared' ? 'PAGO' : payment.status === 'cancelled' ? 'CANCELADO' : 'ORIGINAL'}
                watermark={payment.status === 'cancelled' ? 'CANCELADO' : undefined}
                issuer={{
                    title: 'Emitente',
                    name: issuer.company_name || 'Empresa',
                    logoPath: settings.logo_dark || settings.logo_light || null,
                    address: issuer.company_address,
                    cityLine: buildPartyCityLine(issuer),
                    countryLine: buildPartyCountryLine(issuer),
                    taxLabel: issuerTaxLabel,
                    taxNumber: issuer.tax_number,
                    phone: issuer.company_telephone,
                    email: issuer.company_email,
                    website: settings.company_website || settings.website,
                    registration: issuer.registration_number,
                }}
                recipient={{
                    title: 'Para',
                    name: customerName,
                    address: recipientAddress || undefined,
                    cityLine: recipientCityLine || undefined,
                    countryLine: recipientCountryLine || undefined,
                    taxLabel: recipientTaxLabel,
                    taxNumber: recipientTaxNumber,
                    phone: customerProfile?.contact_person_mobile || undefined,
                    email: recipientEmail || undefined,
                }}
                statusPills={[
                    { label: paymentStatusLabel(payment.status), tone: paymentStatusTone },
                    { label: paymentApprovalLabel, tone: approvalTone },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(payment.payment_date, page.props) },
                    { label: 'Cliente', value: customerName },
                    { label: 'Conta bancária', value: payment.bank_account?.account_name || '-' },
                    { label: 'Banco', value: payment.bank_account?.bank_name || '-' },
                    { label: 'Método', value: paymentMethodLabel(payment.payment_method, t) },
                    { label: 'Referência', value: payment.reference_number || '-' },
                    { label: 'Moeda', value: payment.currency_code || 'MZN' },
                    ...(payment.currency_code && payment.currency_code !== 'MZN'
                        ? [
                            { label: 'Taxa de câmbio', value: String(payment.exchange_rate || 1) },
                            { label: 'Valor estrangeiro', value: formatDocumentMoney(payment.foreign_amount || 0, { ...settings, currencySymbol: payment.currency_code }) },
                        ]
                        : []),
                    { label: 'Operador', value: authUser?.name || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Total recebido', value: formatDocumentMoney(amount, settings), emphasis: true },
                    { label: 'Aplicado em facturas', value: formatDocumentMoney(allocationsTotal, settings) },
                    { label: 'Notas de crédito aplicadas', value: formatDocumentMoney(creditNotesTotal, settings) },
                    { label: 'Saldo remanescente', value: formatDocumentMoney(residualBalance, settings) },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(amount) },
                ]}
                observations={observations}
                bankDetails={[
                    { label: 'Banco', value: payment.bank_account?.bank_name || '-' },
                    { label: 'Conta', value: payment.bank_account?.account_name || '-' },
                    { label: 'Nº Conta', value: payment.bank_account?.account_number || '-' },
                    {
                        label: 'Agência',
                        value: payment.branch?.branch_name || payment.bank_account?.branch?.branch_name || payment.bank_account?.branch_name || '-',
                    },
                ]}
                validationCode={`${documentNumber}-${payment.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
