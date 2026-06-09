import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    formatCurrency,
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolvePurchaseDocumentCounterparty,
} from '@/utils/helpers';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    ReportCard,
    ReportHero,
    ReportKeyValueGrid,
    ReportPill,
    ReportShell,
    ReportSummaryCard,
    ReportTable,
} from '@/components/print/report-kit';
import { PurchaseInvoice } from './types';

interface PrintProps {
    invoice: PurchaseInvoice & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0);

const statusMeta: Record<string, { label: string; tone: 'neutral' | 'info' | 'success' | 'warning' | 'danger' }> = {
    draft: { label: 'Rascunho', tone: 'neutral' },
    posted: { label: 'Emitida', tone: 'info' },
    partial: { label: 'Parcial', tone: 'warning' },
    paid: { label: 'Paga', tone: 'success' },
    overdue: { label: 'Vencida', tone: 'danger' },
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

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { invoice } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);

    const invoiceData = invoice as PrintProps['invoice'];
    const issuer = resolveDocumentIssuer(invoiceData as Record<string, any>, page.props);
    const vendor = resolvePurchaseDocumentCounterparty(invoiceData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const companyTaxNumber = issuer.tax_number || null;
    const vendorTaxLabel = vendor.tax_label || companyTaxLabel;
    const paymentAllocations = Array.isArray(invoiceData.paymentAllocations) ? invoiceData.paymentAllocations : [];
    const totalPaid = paymentAllocations.reduce((sum: number, allocation: any) => sum + toNumber(allocation?.allocated_amount ?? allocation?.payment?.payment_amount), 0) || toNumber(invoiceData.paid_amount);
    const totalAmount = toNumber(invoiceData.total_amount);
    const changeAmount = Math.max(totalPaid - totalAmount, 0);
    const paymentStatus = totalPaid >= totalAmount && totalAmount > 0
        ? 'Pago'
        : totalPaid > 0
            ? 'Parcial'
            : 'Pendente';
    const documentStatus = statusMeta[invoiceData.display_status || invoiceData.status || 'draft'] || statusMeta.draft;

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.document-print-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `purchase-invoice-${invoiceData.invoice_number}.pdf`,
                image: { type: 'jpeg' as const, quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' as const },
            };

            try {
                await saveElementAsPdf(printContent as HTMLElement, opt);
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const itemRows = (invoiceData.items ?? []).map((item, index) => {
        const taxes = item.taxes ?? [];
        const taxLabel = taxes.length > 0
            ? taxes.map((tax) => `${tax.tax_name} (${tax.tax_rate}%)`).join(', ')
            : item.tax_percentage > 0
                ? `${item.tax_percentage}%`
                : '0%';

        return (
            <tr key={index} className="report-page-break-inside-avoid">
                <td className="px-4 py-4 align-top">
                    <div className="font-semibold text-slate-900">{item.product?.name || '-'}</div>
                    {item.product?.sku && <div className="mt-1 text-xs text-slate-500">SKU: {item.product.sku}</div>}
                    {item.product?.description && <div className="mt-1 text-xs leading-5 text-slate-500">{item.product.description}</div>}
                </td>
                <td className="px-4 py-4 text-right align-top tabular-nums">{item.quantity}</td>
                <td className="px-4 py-4 text-right align-top tabular-nums">{formatCurrency(item.unit_price)}</td>
                <td className="px-4 py-4 text-right align-top tabular-nums">
                    {toNumber(item.discount_amount) > 0 ? `-${formatCurrency(item.discount_amount)}` : formatCurrency(0)}
                </td>
                <td className="px-4 py-4 text-right align-top">
                    <div className="tabular-nums">{taxLabel}</div>
                    {item.tax_exemption_reason && (
                        <div className="mt-1 text-xs leading-5 text-amber-700">
                            Isenção: {item.tax_exemption_reason}
                        </div>
                    )}
                </td>
                <td className="px-4 py-4 text-right align-top font-semibold tabular-nums">{formatCurrency(item.total_amount)}</td>
            </tr>
        );
    });

    return (
        <ReportShell>
            <Head title={`Factura de compra #${invoiceData.invoice_number}`} />

            {isDownloading && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="rounded-2xl bg-white px-6 py-5 shadow-xl">
                        <div className="flex items-center gap-3">
                            <div className="h-6 w-6 animate-spin rounded-full border-b-2 border-emerald-600" />
                            <p className="text-lg font-semibold text-slate-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="document-print-container space-y-6">
                <ReportHero
                    title="Factura de compra"
                    subtitle={paymentStatus === 'Pago' ? 'Factura-recibo' : 'Documento de aquisição'}
                    issuerTitle="Emitente"
                    issuerLines={[
                        issuer.company_name || 'Empresa',
                        issuer.company_address,
                        [issuer.company_city, issuer.company_state, issuer.company_zipcode].filter(Boolean).join(', '),
                        issuer.company_country,
                        issuer.company_telephone ? `Telefone: ${issuer.company_telephone}` : null,
                        issuer.company_email ? `E-mail: ${issuer.company_email}` : null,
                        companyTaxNumber ? `${companyTaxLabel}: ${companyTaxNumber}` : null,
                    ].filter(Boolean) as React.ReactNode[]}
                    documentLabel="Documento"
                    documentNumber={`#${invoiceData.invoice_number}`}
                    statusPills={[
                        { label: 'Compra', tone: 'info' },
                        { label: documentStatus.label, tone: documentStatus.tone },
                        { label: paymentStatus, tone: paymentStatus === 'Pago' ? 'success' : paymentStatus === 'Parcial' ? 'warning' : 'neutral' },
                    ]}
                    meta={[
                        { label: 'Data', value: formatDate(invoiceData.invoice_date) },
                        { label: 'Vencimento', value: formatDate(invoiceData.due_date) },
                        { label: 'Série', value: invoiceData.document_series || '-' },
                        { label: 'Sequência', value: invoiceData.document_sequence || '-' },
                    ]}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <ReportCard title="Fornecedor" subtitle="Dados do emitente externo">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Nome', value: vendor.company_name || vendor.name || invoiceData.vendor?.name || '-' },
                                { label: vendorTaxLabel || 'NUIT', value: vendor.tax_number || '-' },
                                { label: 'E-mail', value: vendor.email || '-' },
                                { label: 'Código', value: invoiceData.vendor_details?.vendor_code || '-' },
                                { label: 'Morada', value: vendor.billing_address?.address_line_1 || '-', span: 2 },
                                { label: 'Cidade', value: [vendor.billing_address?.city, vendor.billing_address?.state, vendor.billing_address?.zip_code].filter(Boolean).join(' - ') || '-', span: 2 },
                            ]}
                        />
                    </ReportCard>

                    <ReportCard title="Pagamentos" subtitle="Liquidação da factura">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Estado', value: paymentStatus },
                                { label: 'Troco', value: formatCurrency(changeAmount) },
                                { label: 'Total recebido', value: formatCurrency(totalPaid) },
                                { label: 'Forma principal', value: paymentMethodLabel(paymentAllocations[0]?.payment?.payment_method || paymentAllocations[0]?.payment_method) },
                            ]}
                        />
                    </ReportCard>
                </div>

                <ReportTable headers={['Descrição', 'Qtd', 'Preço líquido', 'Desconto', 'IVA', 'Total']}>
                    {itemRows}
                </ReportTable>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(340px,0.9fr)]">
                    <ReportCard title="Observações" subtitle="Condições comerciais">
                        <div className="space-y-4 text-sm leading-6 text-slate-700">
                            <div>
                                <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Termos de pagamento</div>
                                <div className="mt-1">{invoiceData.payment_terms || 'Sem condições adicionais.'}</div>
                            </div>
                            <div>
                                <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Notas</div>
                                <div className="mt-1 whitespace-pre-line">{invoiceData.notes || 'Sem notas adicionais.'}</div>
                            </div>
                        </div>
                    </ReportCard>

                    <ReportSummaryCard
                        title="Resumo"
                        subtitle="Totais do documento"
                        rows={[
                            { label: 'Subtotal', value: formatCurrency(invoiceData.subtotal) },
                            { label: 'Desconto', value: `-${formatCurrency(invoiceData.discount_amount)}` },
                            { label: 'IVA', value: formatCurrency(invoiceData.tax_amount) },
                            { label: 'Total', value: formatCurrency(invoiceData.total_amount), emphasis: true },
                            { label: 'Recebido', value: formatCurrency(totalPaid) },
                            { label: 'Saldo', value: formatCurrency(toNumber(invoiceData.balance_amount)) },
                        ]}
                    />
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <ReportPill tone={paymentStatus === 'Pago' ? 'success' : paymentStatus === 'Parcial' ? 'warning' : 'neutral'}>{paymentStatus}</ReportPill>
                    <ReportPill tone={documentStatus.tone}>{documentStatus.label}</ReportPill>
                    {paymentStatus === 'Pago' && <ReportPill tone="success">Factura-recibo</ReportPill>}
                </div>
            </div>
        </ReportShell>
    );
}
