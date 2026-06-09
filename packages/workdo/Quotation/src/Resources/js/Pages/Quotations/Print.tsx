import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    formatCurrency,
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolveSalesDocumentCounterparty,
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
import { Quotation } from './types';

interface PrintProps {
    quotation: Quotation & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0);

const statusMeta: Record<string, { label: string; tone: 'neutral' | 'info' | 'success' | 'warning' | 'danger' }> = {
    draft: { label: 'Rascunho', tone: 'neutral' },
    sent: { label: 'Enviada', tone: 'info' },
    accepted: { label: 'Aceite', tone: 'success' },
    rejected: { label: 'Rejeitada', tone: 'danger' },
    expired: { label: 'Expirada', tone: 'warning' },
};

export default function Print() {
    const { t } = useTranslation();
    const { quotation } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    const quotationData = quotation as PrintProps['quotation'];
    const issuer = resolveDocumentIssuer(quotationData as Record<string, any>);
    const customer = resolveSalesDocumentCounterparty(quotationData as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel();
    const companyTaxNumber = issuer.tax_number || null;
    const documentStatus = statusMeta[quotationData.display_status || quotationData.status || 'draft'] || statusMeta.draft;

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
                filename: `quotation-${quotationData.quotation_number}.pdf`,
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

    const itemRows = (quotationData.items ?? []).map((item, index) => {
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
                </td>
                <td className="px-4 py-4 text-right align-top font-semibold tabular-nums">{formatCurrency(item.total_amount)}</td>
            </tr>
        );
    });

    return (
        <ReportShell>
            <Head title={`Cotação #${quotationData.quotation_number}`} />

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
                    title="Cotação / Orçamento"
                    subtitle={quotationData.status === 'draft' ? 'Proforma' : 'Proposta comercial'}
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
                    documentNumber={`#${quotationData.quotation_number}`}
                    statusPills={[
                        { label: 'Comercial', tone: 'info' },
                        { label: documentStatus.label, tone: documentStatus.tone },
                        { label: quotationData.converted_to_invoice ? 'Convertida em factura' : 'Não convertida', tone: quotationData.converted_to_invoice ? 'success' : 'neutral' },
                    ]}
                    meta={[
                        { label: 'Data', value: formatDate(quotationData.quotation_date) },
                        { label: 'Validade', value: formatDate(quotationData.due_date) },
                        { label: 'Revisão', value: quotationData.revision_number || '-' },
                        { label: 'Factura associada', value: quotationData.invoice_id || '-' },
                    ]}
                    note="Documento comercial não fiscal. Não serve como factura."
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <ReportCard title="Cliente" subtitle="Dados da proposta">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Nome', value: customer.company_name || customer.name || quotationData.customer?.name || '-' },
                                { label: customer.tax_label || companyTaxLabel || 'NUIT', value: customer.tax_number || quotationData.customer_details?.tax_number || '-' },
                                { label: 'E-mail', value: customer.email || quotationData.customer?.email || '-' },
                                { label: 'Código', value: quotationData.customer_details?.customer_code || '-' },
                                { label: 'Morada', value: customer.billing_address?.address_line_1 || '-', span: 2 },
                                { label: 'Cidade', value: [customer.billing_address?.city, customer.billing_address?.state, customer.billing_address?.zip_code].filter(Boolean).join(' - ') || '-', span: 2 },
                            ]}
                        />
                    </ReportCard>

                    <ReportCard title="Condições" subtitle="Dados comerciais">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Prazo', value: quotationData.payment_terms || 'Sem condições adicionais.' },
                                { label: 'Estado', value: documentStatus.label },
                                { label: 'Conversão', value: quotationData.converted_to_invoice ? `Sim #${quotationData.invoice_id || '-'}` : 'Não' },
                                { label: 'Armazém', value: quotationData.warehouse?.name || '-' },
                            ]}
                        />
                    </ReportCard>
                </div>

                <ReportTable headers={['Descrição', 'Qtd', 'Preço líquido', 'Desconto', 'IVA', 'Total']}>
                    {itemRows}
                </ReportTable>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(340px,0.9fr)]">
                    <ReportCard title="Notas" subtitle="Instruções e observações">
                        <div className="space-y-4 text-sm leading-6 text-slate-700">
                            <div>
                                <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Condições</div>
                                <div className="mt-1">{quotationData.payment_terms || 'Sem condições adicionais.'}</div>
                            </div>
                            <div>
                                <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Notas</div>
                                <div className="mt-1 whitespace-pre-line">{quotationData.notes || 'Sem notas adicionais.'}</div>
                            </div>
                        </div>
                    </ReportCard>

                    <ReportSummaryCard
                        title="Resumo"
                        subtitle="Totais do documento"
                        rows={[
                            { label: 'Subtotal', value: formatCurrency(quotationData.subtotal) },
                            { label: 'Desconto', value: `-${formatCurrency(quotationData.discount_amount)}` },
                            { label: 'IVA', value: formatCurrency(quotationData.tax_amount) },
                            { label: 'Total', value: formatCurrency(quotationData.total_amount), emphasis: true },
                        ]}
                    />
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <ReportPill tone="info">Documento comercial</ReportPill>
                    <ReportPill tone={documentStatus.tone}>{documentStatus.label}</ReportPill>
                    {quotationData.converted_to_invoice ? <ReportPill tone="success">Convertida em factura</ReportPill> : <ReportPill tone="neutral">Conversão pendente</ReportPill>}
                </div>
            </div>
        </ReportShell>
    );
}
