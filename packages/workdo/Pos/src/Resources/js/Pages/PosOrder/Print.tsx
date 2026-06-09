import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatCurrency,
    formatDate,
    getCompanySetting,
    resolveCompanyTaxLabel,
    resolveCompanyTaxNumber,
} from '@/utils/helpers';
import {
    ReportCard,
    ReportHero,
    ReportKeyValueGrid,
    ReportPill,
    ReportShell,
    ReportSummaryCard,
    ReportTable,
} from '@/components/print/report-kit';

interface PosItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: {
        id: number;
        name: string;
        sku?: string;
    };
    taxes?: Array<{ id?: number; tax_name: string; rate: number }>;
    tax_amount?: number;
}

interface PosSale {
    id: number;
    sale_number: string;
    document_series?: string;
    payment_method?: string;
    paid_amount?: number;
    customer?: {
        name: string;
        email?: string;
    };
    warehouse?: {
        name: string;
    };
    creator?: {
        name?: string;
    };
    bankAccount?: {
        account_name?: string;
        account_number?: string;
        branch_name?: string;
    };
    subtotal: number;
    discount_amount: number;
    tax_amount?: number;
    total: number;
    total_amount?: number;
    created_at: string;
    pos_date?: string;
    items: PosItem[];
}

interface PrintProps {
    sale: PosSale & Record<string, any>;
}

const toNumber = (value: unknown): number => Number(value ?? 0);

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
    const { sale } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);

    const saleData = sale as PrintProps['sale'];
    const companySettings = ((page.props as any).companyAllSetting || (page.props as any).adminAllSetting || {});
    const companyTaxLabel = resolveCompanyTaxLabel(companySettings);
    const companyTaxNumber = resolveCompanyTaxNumber(companySettings);
    const receivedAmount = toNumber(saleData.paid_amount || saleData.total_amount || saleData.total);
    const totalAmount = toNumber(saleData.total_amount || saleData.total);
    const changeAmount = Math.max(receivedAmount - totalAmount, 0);
    const hasTax = toNumber(saleData.tax_amount) > 0 || (saleData.items ?? []).some((item) => toNumber(item.tax_amount) > 0);

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
                margin: 0.15,
                filename: `pos-sale-${saleData.sale_number}.pdf`,
                image: { type: 'jpeg' as const, quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: [80, 297], orientation: 'portrait' as const },
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

    const itemRows = (saleData.items ?? []).map((item, index) => {
        const taxText = item.taxes && item.taxes.length > 0
            ? item.taxes.map((tax) => `${tax.tax_name} (${tax.rate}%)`).join(', ')
            : toNumber(item.tax_amount) > 0
                ? 'IVA incluído'
                : '-';

        return (
            <tr key={index} className="report-page-break-inside-avoid">
                <td className="px-4 py-3 align-top">
                    <div className="font-semibold text-slate-900">{item.product?.name}</div>
                    {item.product?.sku && <div className="mt-1 text-xs text-slate-500">SKU: {item.product.sku}</div>}
                </td>
                <td className="px-4 py-3 text-right align-top tabular-nums">{item.quantity}</td>
                <td className="px-4 py-3 text-right align-top tabular-nums">{formatCurrency(item.price)}</td>
                <td className="px-4 py-3 text-right align-top text-xs leading-5 text-slate-600">{taxText}</td>
                <td className="px-4 py-3 text-right align-top font-semibold tabular-nums">{formatCurrency(item.total_amount ?? item.total)}</td>
            </tr>
        );
    });

    return (
        <ReportShell className="bg-white">
            <Head title={`Talão POS #${saleData.sale_number}`} />

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

            <div className="document-print-container space-y-4">
                <ReportHero
                    title="Talão POS"
                    subtitle={hasTax ? 'IVA incluído' : 'Sem IVA'}
                    issuerTitle="Empresa"
                    issuerLines={[
                        getCompanySetting('company_name') || 'Empresa',
                        getCompanySetting('company_address'),
                        [getCompanySetting('company_city'), getCompanySetting('company_state')].filter(Boolean).join(', '),
                        [getCompanySetting('company_country'), getCompanySetting('company_zipcode')].filter(Boolean).join(' - '),
                        getCompanySetting('company_telephone') ? `Telefone: ${getCompanySetting('company_telephone')}` : null,
                        getCompanySetting('company_email') ? `E-mail: ${getCompanySetting('company_email')}` : null,
                        companyTaxNumber ? `${companyTaxLabel}: ${companyTaxNumber}` : null,
                    ].filter(Boolean) as React.ReactNode[]}
                    documentLabel="Venda"
                    documentNumber={`#${saleData.sale_number}`}
                    statusPills={[
                        { label: 'POS', tone: 'info' },
                        { label: paymentMethodLabel(saleData.payment_method), tone: 'neutral' },
                        { label: hasTax ? 'IVA incluído' : 'Sem IVA', tone: hasTax ? 'success' : 'neutral' },
                    ]}
                    meta={[
                        { label: 'Data', value: formatDate(saleData.pos_date || saleData.created_at) },
                        { label: 'Hora', value: new Date(saleData.created_at).toLocaleTimeString() },
                        { label: 'Terminal/Série', value: saleData.document_series || '-' },
                        { label: 'Operador', value: saleData.creator?.name || '-' },
                    ]}
                    note={hasTax ? 'Talão emitido com IVA incluído nos preços apresentados.' : 'Talão emitido sem IVA destacado.'}
                />

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(280px,0.85fr)]">
                    <ReportCard title="Cliente" subtitle="Venda rápida">
                        <ReportKeyValueGrid
                            columns={2}
                        items={[
                            { label: 'Cliente', value: saleData.customer?.name || 'Cliente ocasional' },
                            { label: 'E-mail', value: saleData.customer?.email || '-' },
                            { label: 'Armazém', value: saleData.warehouse?.name || '-' },
                            { label: 'Método', value: paymentMethodLabel(saleData.payment_method) },
                            { label: 'Caixa/Banco', value: saleData.bankAccount?.account_name || saleData.bankAccount?.branch_name || saleData.bankAccount?.account_number || '-' },
                        ]}
                    />
                </ReportCard>

                    <ReportSummaryCard
                        title="Resumo"
                        subtitle="Totais do talão"
                        rows={[
                            { label: 'Subtotal', value: formatCurrency(saleData.subtotal) },
                            { label: 'Desconto', value: `-${formatCurrency(saleData.discount_amount)}` },
                            { label: 'IVA', value: formatCurrency(saleData.tax_amount || 0) },
                            { label: 'Total', value: formatCurrency(totalAmount), emphasis: true },
                            { label: 'Recebido', value: formatCurrency(receivedAmount) },
                            { label: 'Troco', value: formatCurrency(changeAmount) },
                        ]}
                    />
                </div>

                <ReportTable headers={['Produto', 'Qtd', 'Preço unitário', 'IVA', 'Total']}>
                    {itemRows}
                </ReportTable>

                <div className="flex flex-wrap items-center gap-2">
                    <ReportPill tone="info">POS</ReportPill>
                    <ReportPill tone={hasTax ? 'success' : 'neutral'}>{hasTax ? 'IVA incluído' : 'Sem IVA'}</ReportPill>
                    <ReportPill tone={paymentMethodLabel(saleData.payment_method) === 'Caixa' ? 'success' : 'info'}>{paymentMethodLabel(saleData.payment_method)}</ReportPill>
                </div>
            </div>
        </ReportShell>
    );
}
