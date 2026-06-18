import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolveSalesDocumentCounterparty,
} from '@/utils/helpers';
import { saveElementAsPdf } from '@/utils/pdf';
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
import { Quotation } from './types';

interface PrintProps {
    quotation: Quotation & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const statusLabel: Record<string, string> = {
    draft: 'Rascunho',
    sent: 'Enviada',
    accepted: 'Aceite',
    rejected: 'Rejeitada',
    expired: 'Expirada',
};

const taxLabelForItem = (item: any): string => {
    const taxes = item.taxes || [];
    if (taxes.length > 0) {
        return taxes.map((tax: any) => `${tax.tax_name || 'IVA'} ${tax.tax_rate || tax.rate}%`).join(', ');
    }

    return toNumber(item.tax_percentage) > 0 ? `IVA ${item.tax_percentage}%` : '0%';
};

const sanitizeFilename = (value: string): string => value.replace(/[^\w.-]+/g, '-');

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { quotation } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);

    const quotationData = quotation as PrintProps['quotation'];
    const settings = (page.props as any).companyAllSetting || {};
    const issuer = resolveDocumentIssuer(quotationData as Record<string, any>, page.props);
    const customer = resolveSalesDocumentCounterparty(quotationData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const counterpartyTaxLabel = customer.tax_label || companyTaxLabel;
    const documentNumber = buildStructuredDocumentNumber({
        prefix: 'COT',
        series: quotationData.document_series,
        sequence: quotationData.document_sequence,
        number: quotationData.quotation_number,
        date: quotationData.quotation_date,
    });
    const billing = customer.billing_address || quotationData.customer_details?.billing_address || {};
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
                    buildCommercialDocumentPdfOptions(`cotacao-${sanitizeFilename(documentNumber)}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const lines = (quotationData.items || []).map((item: any) => {
        const quantity = toNumber(item.quantity);
        const unitPrice = toNumber(item.unit_price);
        const discount = toNumber(item.discount_amount);
        const netUnitPrice = quantity > 0 ? Math.max(((unitPrice * quantity) - discount) / quantity, 0) : unitPrice;

        return {
            reference: item.product?.sku || String(item.product_id || item.id),
            description: (
                <>
                    <div>{item.product?.name || '-'}</div>
                    {item.product?.description && <div className="text-[10px] font-normal text-slate-600">{item.product.description}</div>}
                </>
            ),
            unit: item.product?.unit || 'UN',
            quantity: formatDocumentQuantity(quantity),
            unitPrice: formatDocumentMoney(unitPrice, settings),
            discount: toNumber(item.discount_percentage) > 0
                ? `${item.discount_percentage}%`
                : discount > 0 ? formatDocumentMoney(discount, settings) : '-',
            netPrice: formatDocumentMoney(netUnitPrice, settings),
            tax: taxLabelForItem(item),
            taxAmount: formatDocumentMoney(toNumber(item.tax_amount), settings),
            total: formatDocumentMoney(item.total_amount, settings),
        };
    });

    return (
        <div className="min-h-screen bg-slate-100 py-6 print:bg-white print:py-0">
            <Head title={`Cotação #${documentNumber}`} />

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
                title="COTAÇÃO / ORÇAMENTO"
                subtitle="Documento comercial não fiscal"
                documentLabel="COM"
                documentNumber={documentNumber}
                copyLabel="ORIGINAL"
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
                    title: 'Para:',
                    name: customer.company_name || customer.name || quotationData.customer?.name || 'Cliente',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: counterpartyTaxLabel,
                    taxNumber: customer.tax_number || quotationData.customer_details?.tax_number,
                    phone: customer.phone || quotationData.customer_details?.contact_person_mobile,
                    email: customer.email || quotationData.customer?.email || quotationData.customer_details?.contact_person_email,
                }}
                statusPills={[
                    { label: statusLabel[quotationData.display_status || quotationData.status] || quotationData.status || 'Rascunho', tone: 'neutral' },
                    { label: quotationData.converted_to_invoice ? 'Convertida em factura' : 'Não convertida', tone: quotationData.converted_to_invoice ? 'success' : 'neutral' },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(quotationData.quotation_date, page.props) },
                    { label: 'Validade', value: formatDate(quotationData.due_date, page.props) },
                    { label: 'Nome vendedor', value: authUser?.name || '-' },
                    { label: 'Pagamento', value: quotationData.payment_terms || 'A combinar' },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Local de descarga', value: quotationData.warehouse?.name || billing.city || '-' },
                    { label: 'Revisão', value: quotationData.revision_number || '-' },
                    { label: 'Factura associada', value: quotationData.invoice_id || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Total venda', value: formatDocumentMoney(quotationData.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(quotationData.discount_amount, settings) },
                    { label: 'Total com descontos', value: formatDocumentMoney(Math.max(toNumber(quotationData.subtotal) - toNumber(quotationData.discount_amount), 0), settings) },
                    { label: 'IVA', value: formatDocumentMoney(quotationData.tax_amount, settings) },
                    { label: 'Total com IVA', value: formatDocumentMoney(quotationData.total_amount, settings), emphasis: true },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(quotationData.total_amount) },
                ]}
                observations={quotationData.notes || [
                    'Validade da proposta conforme data indicada.',
                    'Sujeito às condições de venda em vigor.',
                    'Sujeito à confirmação de stock.',
                ].join('\n')}
                legalNotice="Documento comercial. Não serve como factura."
                validationCode={`${documentNumber}-${quotationData.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
