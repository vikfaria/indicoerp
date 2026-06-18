import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolvePurchaseDocumentCounterparty,
} from '@/utils/helpers';
import {
    buildCommercialDocumentPdfOptions,
    buildPartyCityLine,
    buildPartyCountryLine,
    buildStructuredDocumentNumber,
    COMMERCIAL_DOCUMENT_CONTAINER_CLASS,
    CommercialDocumentTemplate,
    formatDocumentMoney,
    formatDocumentQuantity,
    moneyToPortugueseWords,
} from '@/components/documents/commercial-document-template';

interface DebitNote {
    id: number;
    debit_note_number: string;
    debit_note_date: string;
    document_series?: string;
    document_sequence?: number;
    vendor?: { name?: string; email?: string };
    invoice?: { invoice_number?: string };
    total_amount: number | string;
    applied_amount: number | string;
    balance_amount: number | string;
    subtotal: number | string;
    tax_amount: number | string;
    discount_amount: number | string;
    status: string;
    reason: string;
    notes?: string;
    purchase_return?: { return_number: string };
    items: Array<{
        id: number;
        product?: { name?: string; sku?: string; description?: string; unit?: string };
        quantity: number | string;
        unit_price: number | string;
        discount_amount: number | string;
        tax_percentage: number | string;
        tax_amount: number | string;
        total_amount: number | string;
        taxes?: Array<{ tax_name: string; tax_rate: number | string }>;
    }>;
}

interface PrintProps {
    debitNote: DebitNote & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const statusLabel: Record<string, string> = {
    draft: 'Rascunho',
    approved: 'Aprovada',
    posted: 'Lançada',
    applied: 'Aplicada',
    cancelled: 'Cancelada',
};

const formatReason = (reason?: string): string => {
    if (!reason) return '-';

    return reason.split('_').map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1)).join(' ');
};

const taxLabelForItem = (item: any): string => {
    const taxes = item.taxes || [];
    if (taxes.length > 0) {
        return taxes.map((tax: any) => `${tax.tax_name || 'IVA'} ${tax.tax_rate}%`).join(', ');
    }

    return toNumber(item.tax_percentage) > 0 ? `IVA ${item.tax_percentage}%` : '0%';
};

const sanitizeFilename = (value: string): string => value.replace(/[^\w.-]+/g, '-');

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { debitNote } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const note = debitNote as PrintProps['debitNote'];
    const issuer = resolveDocumentIssuer(note as Record<string, any>, page.props);
    const counterparty = resolvePurchaseDocumentCounterparty(note as Record<string, any>);
    const issuerTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const counterpartyTaxLabel = counterparty.tax_label || issuerTaxLabel;
    const documentNumber = buildStructuredDocumentNumber({
        prefix: 'ND',
        series: note.document_series,
        sequence: note.document_sequence,
        number: note.debit_note_number,
        date: note.debit_note_date,
    });
    const billing = counterparty.billing_address || {};
    const authUser = (page.props as any).auth?.user;

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
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
                    buildCommercialDocumentPdfOptions(`nota-debito-${sanitizeFilename(documentNumber)}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const lines = (note.items || []).map((item) => {
        const quantity = toNumber(item.quantity);
        const unitPrice = toNumber(item.unit_price);
        const discount = toNumber(item.discount_amount);
        const netUnitPrice = quantity > 0 ? Math.max(((unitPrice * quantity) - discount) / quantity, 0) : unitPrice;

        return {
            reference: item.product?.sku || String(item.id),
            description: (
                <>
                    <div>{item.product?.name || '-'}</div>
                    {item.product?.description && <div className="text-[10px] font-normal text-slate-500">{item.product.description}</div>}
                </>
            ),
            unit: item.product?.unit || 'UN',
            quantity: formatDocumentQuantity(quantity),
            unitPrice: formatDocumentMoney(unitPrice, settings),
            discount: discount > 0 ? formatDocumentMoney(discount, settings) : '-',
            netPrice: formatDocumentMoney(netUnitPrice, settings),
            tax: taxLabelForItem(item),
            taxAmount: formatDocumentMoney(toNumber(item.tax_amount), settings),
            total: formatDocumentMoney(item.total_amount, settings),
        };
    });

    return (
        <div className="min-h-screen bg-slate-100 py-6 print:bg-white print:py-0">
            <Head title={`Nota de débito #${documentNumber}`} />

            {isDownloading && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="rounded-lg bg-white px-5 py-4 shadow-xl">
                        <div className="flex items-center gap-3">
                            <div className="h-5 w-5 animate-spin rounded-full border-b-2 border-emerald-600" />
                            <p className="text-sm font-semibold text-slate-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <CommercialDocumentTemplate
                title="NOTA DE DÉBITO"
                subtitle="Acréscimo ou regularização de valores"
                documentLabel="Nota de débito"
                documentNumber={documentNumber}
                copyLabel={note.status === 'cancelled' ? 'CANCELADA' : 'ORIGINAL'}
                watermark={note.status === 'cancelled' ? 'CANCELADA' : undefined}
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
                    name: counterparty.company_name || counterparty.name || note.vendor?.name || 'Fornecedor',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: counterpartyTaxLabel,
                    taxNumber: counterparty.tax_number,
                    phone: counterparty.phone,
                    email: counterparty.email || note.vendor?.email,
                }}
                statusPills={[
                    { label: statusLabel[note.status] || note.status || 'Rascunho', tone: note.status === 'cancelled' ? 'danger' : 'warning' },
                    { label: 'IVA adicional', tone: 'warning' },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(note.debit_note_date, page.props) },
                    { label: 'Factura original', value: note.invoice?.invoice_number || '-' },
                    { label: 'Devolução', value: note.purchase_return?.return_number || '-' },
                    { label: 'Motivo', value: formatReason(note.reason) },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Série', value: note.document_series || '-' },
                    { label: 'Sequência', value: note.document_sequence || '-' },
                    { label: 'Operador', value: authUser?.name || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Subtotal', value: formatDocumentMoney(note.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(note.discount_amount, settings) },
                    { label: 'Valor tributável', value: formatDocumentMoney(Math.max(toNumber(note.subtotal) - toNumber(note.discount_amount), 0), settings) },
                    { label: 'IVA adicional', value: formatDocumentMoney(note.tax_amount, settings) },
                    { label: 'Total adicional', value: formatDocumentMoney(note.total_amount, settings), emphasis: true },
                    { label: 'Valor aplicado', value: formatDocumentMoney(note.applied_amount, settings) },
                    { label: 'Saldo remanescente', value: formatDocumentMoney(note.balance_amount, settings) },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(note.total_amount) },
                ]}
                observations={note.notes || [
                    `Motivo: ${formatReason(note.reason)}`,
                    'A nota de débito regulariza valores adicionais ou em falta.',
                    'IVA adicional indicado conforme aplicável.',
                ].join('\n')}
                validationCode={`${documentNumber}-${note.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
