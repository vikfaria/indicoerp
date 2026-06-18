import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolveSalesDocumentCounterparty,
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
import { SalesReturn } from './types';

interface PrintProps {
    return: SalesReturn & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const statusLabel: Record<string, string> = {
    draft: 'Rascunho',
    approved: 'Aprovada',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

const formatReason = (reason?: string): string => {
    if (!reason) return '-';

    return reason.split('_').map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1)).join(' ');
};

const taxLabelForItem = (item: any): string => {
    const taxes = item.taxes || [];
    if (taxes.length > 0) {
        return taxes.map((tax: any) => `${tax.tax_name || tax.name || 'IVA'} ${tax.tax_rate || tax.rate}%`).join(', ');
    }

    return toNumber(item.tax_percentage) > 0 ? `IVA ${item.tax_percentage}%` : '0%';
};

const sanitizeFilename = (value: string): string => value.replace(/[^\w.-]+/g, '-');

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { return: salesReturn } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const returnData = salesReturn as PrintProps['return'];
    const issuer = resolveDocumentIssuer(returnData as Record<string, any>, page.props);
    const customer = resolveSalesDocumentCounterparty(returnData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const customerTaxLabel = customer.tax_label || companyTaxLabel;
    const willCreateCreditNote = returnData.status === 'approved' || returnData.status === 'completed';
    const documentNumber = buildStructuredDocumentNumber({
        prefix: willCreateCreditNote ? 'NC' : 'GD',
        series: returnData.document_series,
        sequence: returnData.document_sequence,
        number: returnData.return_number,
        date: returnData.return_date,
    });
    const billing = customer.billing_address || returnData.customer_details?.billing_address || {};
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
                    buildCommercialDocumentPdfOptions(`devolucao-venda-${sanitizeFilename(documentNumber)}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const lines = (returnData.items || []).map((item: any) => {
        const quantity = toNumber(item.return_quantity || item.quantity);
        const unitPrice = toNumber(item.unit_price);
        const discount = toNumber(item.discount_amount);
        const netUnitPrice = quantity > 0 ? Math.max(((unitPrice * quantity) - discount) / quantity, 0) : unitPrice;

        return {
            reference: item.product?.sku || String(item.product_id || item.id),
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
            <Head title={`Devolução de venda #${documentNumber}`} />

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
                title={willCreateCreditNote ? 'NOTA DE CRÉDITO' : 'GUIA DE DEVOLUÇÃO'}
                subtitle={willCreateCreditNote ? 'Regularização da venda original' : 'Documento de devolução física'}
                documentLabel={willCreateCreditNote ? 'Nota de crédito' : 'Guia de devolução'}
                documentNumber={documentNumber}
                copyLabel={returnData.status === 'cancelled' ? 'CANCELADA' : 'ORIGINAL'}
                watermark={returnData.status === 'cancelled' ? 'CANCELADA' : undefined}
                issuer={{
                    title: 'Emitente',
                    name: issuer.company_name || 'Empresa',
                    logoPath: settings.logo_dark || settings.logo_light || null,
                    address: issuer.company_address,
                    cityLine: buildPartyCityLine(issuer),
                    countryLine: buildPartyCountryLine(issuer),
                    taxLabel: companyTaxLabel,
                    taxNumber: issuer.tax_number,
                    phone: issuer.company_telephone,
                    email: issuer.company_email,
                    website: settings.company_website || settings.website,
                    registration: issuer.registration_number,
                }}
                recipient={{
                    title: 'Para',
                    name: customer.company_name || customer.name || returnData.customer?.name || 'Cliente',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: customerTaxLabel,
                    taxNumber: customer.tax_number,
                    phone: customer.phone,
                    email: customer.email || returnData.customer?.email,
                }}
                statusPills={[
                    { label: statusLabel[returnData.status] || returnData.status || 'Rascunho', tone: returnData.status === 'cancelled' ? 'danger' : willCreateCreditNote ? 'success' : 'warning' },
                    { label: willCreateCreditNote ? 'Gera nota de crédito' : 'Devolução física', tone: willCreateCreditNote ? 'success' : 'info' },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(returnData.return_date, page.props) },
                    { label: 'Factura original', value: returnData.original_invoice?.invoice_number || '-' },
                    { label: 'Motivo', value: formatReason(returnData.reason) },
                    { label: 'Armazém', value: returnData.warehouse?.name || '-' },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Série', value: returnData.document_series || '-' },
                    { label: 'Sequência', value: returnData.document_sequence || '-' },
                    { label: 'Operador', value: authUser?.name || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Subtotal', value: formatDocumentMoney(returnData.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(returnData.discount_amount, settings) },
                    { label: 'Valor tributável', value: formatDocumentMoney(Math.max(toNumber(returnData.subtotal) - toNumber(returnData.discount_amount), 0), settings) },
                    { label: 'IVA regularizado', value: formatDocumentMoney(returnData.tax_amount, settings) },
                    { label: 'Total creditado', value: formatDocumentMoney(returnData.total_amount, settings), emphasis: true },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(returnData.total_amount) },
                ]}
                observations={returnData.notes || [
                    `Motivo: ${formatReason(returnData.reason)}`,
                    willCreateCreditNote ? 'IVA regularizado conforme documento original.' : 'Documento de devolução física. Pode originar nota de crédito após aprovação.',
                    'Impacto no stock quando aplicável.',
                ].join('\n')}
                validationCode={`${documentNumber}-${returnData.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
