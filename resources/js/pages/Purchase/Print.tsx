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
import { PurchaseInvoice } from './types';

interface PrintProps {
    invoice: PurchaseInvoice & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const statusLabel: Record<string, string> = {
    draft: 'Rascunho',
    posted: 'Emitida',
    partial: 'Parcial',
    paid: 'Paga',
    overdue: 'Vencida',
};

const paymentMethodLabel = (method?: string | null): string => {
    const map: Record<string, string> = {
        cash: 'Caixa',
        bank_transfer: 'Transferência bancária',
        card: 'Cartão',
        mobile_money: 'Mobile Money',
        cheque: 'Cheque',
        other: 'Outro',
    };

    return method ? (map[method] || method) : '-';
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
    const { invoice } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const invoiceData = invoice as PrintProps['invoice'];
    const issuer = resolveDocumentIssuer(invoiceData as Record<string, any>, page.props);
    const vendor = resolvePurchaseDocumentCounterparty(invoiceData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const vendorTaxLabel = vendor.tax_label || companyTaxLabel;
    const paymentAllocations = Array.isArray(invoiceData.paymentAllocations) ? invoiceData.paymentAllocations : [];
    const totalPaid = paymentAllocations.reduce((sum: number, allocation: any) => sum + toNumber(allocation?.allocated_amount ?? allocation?.payment?.payment_amount), 0) || toNumber(invoiceData.paid_amount);
    const totalAmount = toNumber(invoiceData.total_amount);
    const paymentStatus = totalPaid >= totalAmount && totalAmount > 0 ? 'Pago' : totalPaid > 0 ? 'Parcial' : 'Pendente';
    const documentNumber = buildStructuredDocumentNumber({
        prefix: 'FC',
        series: invoiceData.document_series,
        sequence: invoiceData.document_sequence,
        number: invoiceData.invoice_number,
        date: invoiceData.invoice_date,
    });
    const authUser = (page.props as any).auth?.user;
    const billing = vendor.billing_address || invoiceData.vendor_details?.billing_address || {};
    const primaryPayment = paymentAllocations[0]?.payment || paymentAllocations[0] || {};

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
                    buildCommercialDocumentPdfOptions(`factura-compra-${sanitizeFilename(documentNumber)}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const lines = (invoiceData.items || []).map((item: any) => {
        const quantity = toNumber(item.quantity);
        const unitPrice = toNumber(item.unit_price);
        const discount = toNumber(item.discount_amount);
        const netUnitPrice = quantity > 0 ? Math.max(((unitPrice * quantity) - discount) / quantity, 0) : unitPrice;

        return {
            reference: item.product?.sku || String(item.product_id || item.id),
            description: (
                <>
                    <div>{item.product?.name || '-'}</div>
                    {item.product?.description && <div className="text-[10px] font-normal text-slate-500">{item.product.description}</div>}
                    {item.tax_exemption_reason && <div className="text-[10px] font-normal text-amber-700">Isenção: {item.tax_exemption_reason}</div>}
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
            <Head title={`Factura de compra #${documentNumber}`} />

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
                title="FACTURA DE COMPRA"
                subtitle="Documento de aquisição registado no ERP"
                documentLabel="Compra"
                documentNumber={documentNumber}
                copyLabel="ARQUIVO"
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
                    name: vendor.company_name || vendor.name || invoiceData.vendor?.name || 'Fornecedor',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: vendorTaxLabel,
                    taxNumber: vendor.tax_number,
                    phone: vendor.phone,
                    email: vendor.email || invoiceData.vendor?.email,
                }}
                statusPills={[
                    { label: statusLabel[invoiceData.display_status || invoiceData.status] || invoiceData.status || 'Rascunho', tone: paymentStatus === 'Pago' ? 'success' : 'info' },
                    { label: paymentStatus, tone: paymentStatus === 'Pago' ? 'success' : paymentStatus === 'Parcial' ? 'warning' : 'neutral' },
                ]}
                meta={[
                    { label: 'Data emissão', value: formatDate(invoiceData.invoice_date, page.props) },
                    { label: 'Vencimento', value: formatDate(invoiceData.due_date, page.props) },
                    { label: 'Série', value: invoiceData.document_series || '-' },
                    { label: 'Sequência', value: invoiceData.document_sequence || '-' },
                    { label: 'Pagamento', value: paymentMethodLabel(primaryPayment.payment_method || invoiceData.payment_method) },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Armazém', value: invoiceData.warehouse?.name || '-' },
                    { label: 'Registado por', value: authUser?.name || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Subtotal', value: formatDocumentMoney(invoiceData.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(invoiceData.discount_amount, settings) },
                    { label: 'Valor tributável', value: formatDocumentMoney(Math.max(toNumber(invoiceData.subtotal) - toNumber(invoiceData.discount_amount), 0), settings) },
                    { label: 'IVA', value: formatDocumentMoney(invoiceData.tax_amount, settings) },
                    { label: 'Total', value: formatDocumentMoney(invoiceData.total_amount, settings), emphasis: true },
                    { label: 'Pago', value: formatDocumentMoney(totalPaid, settings) },
                    { label: 'Saldo', value: formatDocumentMoney(toNumber(invoiceData.balance_amount), settings) },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(invoiceData.total_amount) },
                ]}
                observations={invoiceData.notes || [
                    'Documento registado para controlo interno de compras.',
                    invoiceData.payment_terms ? `Condições de pagamento: ${invoiceData.payment_terms}` : null,
                ].filter(Boolean).join('\n')}
                validationCode={`${documentNumber}-${invoiceData.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
