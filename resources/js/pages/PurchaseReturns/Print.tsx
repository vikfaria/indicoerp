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
import { PurchaseReturn } from './types';

interface PrintProps {
    return: PurchaseReturn & Record<string, any>;
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
    const { return: purchaseReturn } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const returnData = purchaseReturn as PrintProps['return'];
    const issuer = resolveDocumentIssuer(returnData as Record<string, any>, page.props);
    const vendor = resolvePurchaseDocumentCounterparty(returnData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const vendorTaxLabel = vendor.tax_label || companyTaxLabel;
    const willCreateDebitNote = returnData.status === 'approved' || returnData.status === 'completed';
    const documentNumber = buildStructuredDocumentNumber({
        prefix: willCreateDebitNote ? 'ND' : 'GD',
        series: returnData.document_series,
        sequence: returnData.document_sequence,
        number: returnData.return_number,
        date: returnData.return_date,
    });
    const billing = vendor.billing_address || returnData.vendor_details?.billing_address || {};
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
                    buildCommercialDocumentPdfOptions(`devolucao-compra-${sanitizeFilename(documentNumber)}.pdf`),
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
            <Head title={`Devolução de compra #${documentNumber}`} />

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
                title={willCreateDebitNote ? 'NOTA DE DÉBITO' : 'GUIA DE DEVOLUÇÃO'}
                subtitle={willCreateDebitNote ? 'Regularização da compra original' : 'Documento de devolução física'}
                documentLabel={willCreateDebitNote ? 'Nota de débito' : 'Guia de devolução'}
                documentNumber={documentNumber}
                copyLabel={returnData.status === 'cancelled' ? 'CANCELADA' : 'ORIGINAL'}
                watermark={returnData.status === 'cancelled' ? 'CANCELADA' : undefined}
                issuer={{
                    title: 'Empresa',
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
                    title: 'Fornecedor',
                    name: vendor.company_name || vendor.name || returnData.vendor?.name || 'Fornecedor',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: vendorTaxLabel,
                    taxNumber: vendor.tax_number,
                    phone: vendor.phone,
                    email: vendor.email || returnData.vendor?.email,
                }}
                statusPills={[
                    { label: statusLabel[returnData.status] || returnData.status || 'Rascunho', tone: returnData.status === 'cancelled' ? 'danger' : willCreateDebitNote ? 'success' : 'warning' },
                    { label: willCreateDebitNote ? 'Gera nota de débito' : 'Devolução física', tone: willCreateDebitNote ? 'success' : 'info' },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(returnData.return_date, page.props) },
                    { label: 'Factura original', value: returnData.original_invoice?.invoice_number || '-' },
                    { label: 'Motivo', value: formatReason(returnData.reason) },
                    { label: 'Armazém', value: returnData.warehouse?.name || '-' },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Série', value: returnData.document_series || '-' },
                    { label: 'Sequência', value: returnData.document_sequence || '-' },
                    { label: 'Registado por', value: authUser?.name || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Subtotal', value: formatDocumentMoney(returnData.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(returnData.discount_amount, settings) },
                    { label: 'Valor tributável', value: formatDocumentMoney(Math.max(toNumber(returnData.subtotal) - toNumber(returnData.discount_amount), 0), settings) },
                    { label: 'IVA regularizado', value: formatDocumentMoney(returnData.tax_amount, settings) },
                    { label: 'Total devolvido', value: formatDocumentMoney(returnData.total_amount, settings), emphasis: true },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(returnData.total_amount) },
                ]}
                observations={returnData.notes || [
                    `Motivo: ${formatReason(returnData.reason)}`,
                    willCreateDebitNote ? 'Regularização preparada para nota de débito.' : 'Documento de devolução física ao fornecedor.',
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
