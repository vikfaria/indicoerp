import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatCurrency,
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolveSalesDocumentCounterparty,
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

interface CreditNote {
    id: number;
    credit_note_number: string;
    credit_note_date: string;
    customer?: {
        name?: string;
        email?: string;
    };
    invoice?: {
        invoice_number?: string;
    };
    total_amount: number | string;
    applied_amount: number | string;
    balance_amount: number | string;
    subtotal: number | string;
    tax_amount: number | string;
    discount_amount: number | string;
    status: string;
    reason: string;
    notes?: string;
    issuer_snapshot?: Record<string, any> | null;
    counterparty_snapshot?: Record<string, any> | null;
    items: Array<{
        id: number;
        product?: {
            name?: string;
            sku?: string;
            description?: string;
        };
        quantity: number | string;
        unit_price: number | string;
        discount_percentage: number | string;
        discount_amount: number | string;
        tax_percentage: number | string;
        tax_amount: number | string;
        total_amount: number | string;
        taxes?: Array<{
            tax_name: string;
            tax_rate: number | string;
        }>;
    }>;
    sales_return?: {
        return_number: string;
    };
    applications: Array<{
        id: number;
        applied_amount: number | string;
        application_date: string;
        payment?: {
            payment_number?: string;
        };
    }>;
}

interface PrintProps {
    creditNote: CreditNote & Record<string, any>;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0);

const statusMeta: Record<string, { label: string; tone: 'neutral' | 'info' | 'success' | 'warning' | 'danger' }> = {
    draft: { label: 'Rascunho', tone: 'neutral' },
    approved: { label: 'Aprovada', tone: 'info' },
    posted: { label: 'Lançada', tone: 'success' },
    applied: { label: 'Aplicada', tone: 'success' },
    cancelled: { label: 'Cancelada', tone: 'danger' },
};

const formatReason = (reason?: string): string => {
    if (!reason) {
        return '-';
    }

    return reason
        .split('_')
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');
};

export default function Print() {
    const { t } = useTranslation();
    const { creditNote } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    const note = creditNote as PrintProps['creditNote'];
    const issuer = resolveDocumentIssuer(note as Record<string, any>);
    const counterparty = resolveSalesDocumentCounterparty(note as Record<string, any>);
    const counterpartyTaxLabel = counterparty.tax_label || issuer.tax_label || getCompanyTaxLabel();
    const issuerTaxLabel = issuer.tax_label || getCompanyTaxLabel();
    const documentStatus = statusMeta[note.status || 'draft'] || statusMeta.draft;

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
                filename: `credit-note-${note.credit_note_number}.pdf`,
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

    const itemRows = (note.items ?? []).map((item, index) => {
        const taxes = item.taxes ?? [];
        const taxLabel = taxes.length > 0
            ? taxes.map((tax) => `${tax.tax_name} (${tax.tax_rate}%)`).join(', ')
            : toNumber(item.tax_percentage) > 0
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
                <td className="px-4 py-4 text-right align-top tabular-nums">{formatCurrency(toNumber(item.unit_price))}</td>
                <td className="px-4 py-4 text-right align-top tabular-nums">{toNumber(item.discount_amount) > 0 ? `-${formatCurrency(toNumber(item.discount_amount))}` : formatCurrency(0)}</td>
                <td className="px-4 py-4 text-right align-top">
                    <div className="tabular-nums">{taxLabel}</div>
                </td>
                <td className="px-4 py-4 text-right align-top font-semibold tabular-nums">{formatCurrency(toNumber(item.total_amount))}</td>
            </tr>
        );
    });

    return (
        <ReportShell>
            <Head title={`Nota de crédito #${note.credit_note_number}`} />

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
                    title="Nota de Crédito"
                    subtitle="Regularização da factura original"
                    issuerTitle="Emitente"
                    issuerLines={[
                        issuer.company_name || 'Empresa',
                        issuer.company_address,
                        [issuer.company_city, issuer.company_state, issuer.company_zipcode].filter(Boolean).join(', '),
                        issuer.company_country,
                        issuer.company_telephone ? `Telefone: ${issuer.company_telephone}` : null,
                        issuer.company_email ? `E-mail: ${issuer.company_email}` : null,
                        issuer.tax_number ? `${issuerTaxLabel}: ${issuer.tax_number}` : null,
                    ].filter(Boolean) as React.ReactNode[]}
                    documentLabel="Documento"
                    documentNumber={`#${note.credit_note_number}`}
                    statusPills={[
                        { label: 'Crédito', tone: 'info' },
                        { label: documentStatus.label, tone: documentStatus.tone },
                        { label: 'IVA regularizado', tone: 'success' },
                    ]}
                    meta={[
                        { label: 'Data', value: formatDate(note.credit_note_date) },
                        { label: 'Factura original', value: note.invoice?.invoice_number ? `#${note.invoice.invoice_number}` : '-' },
                        { label: 'Devolução', value: note.sales_return?.return_number ? `#${note.sales_return.return_number}` : '-' },
                        { label: 'Motivo', value: formatReason(note.reason) },
                    ]}
                    note={note.notes || 'A nota de crédito corrige valores previamente facturados.'}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <ReportCard title="Cliente" subtitle="Destino do crédito">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Nome', value: counterparty.company_name || note.customer?.name || '-' },
                                { label: counterpartyTaxLabel || 'NUIT', value: counterparty.tax_number || '-' },
                                { label: 'E-mail', value: counterparty.email || note.customer?.email || '-' },
                                { label: 'Estado', value: documentStatus.label },
                            ]}
                        />
                    </ReportCard>

                    <ReportCard title="Impacto financeiro" subtitle="Valores creditados e pendentes">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Total da nota', value: formatCurrency(toNumber(note.total_amount)) },
                                { label: 'Valor aplicado', value: formatCurrency(toNumber(note.applied_amount)) },
                                { label: 'Saldo remanescente', value: formatCurrency(toNumber(note.balance_amount)) },
                                { label: 'Motivo', value: formatReason(note.reason) },
                            ]}
                        />
                    </ReportCard>
                </div>

                <ReportTable headers={['Descrição', 'Qtd', 'Preço líquido', 'Desconto', 'IVA', 'Total']}>
                    {itemRows}
                </ReportTable>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(340px,0.9fr)]">
                    <ReportCard title="Aplicações" subtitle="Liquidação da nota">
                        {note.applications.length > 0 ? (
                            <ReportTable headers={['Pagamento', 'Data', 'Valor aplicado']}>
                                {note.applications.map((application) => (
                                    <tr key={application.id} className="report-page-break-inside-avoid">
                                        <td className="px-4 py-3 font-medium">{application.payment?.payment_number || '-'}</td>
                                        <td className="px-4 py-3">{formatDate(application.application_date)}</td>
                                        <td className="px-4 py-3 text-right font-semibold tabular-nums">{formatCurrency(toNumber(application.applied_amount))}</td>
                                    </tr>
                                ))}
                            </ReportTable>
                        ) : (
                            <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                                Sem aplicações registadas.
                            </div>
                        )}
                    </ReportCard>

                    <ReportSummaryCard
                        title="Resumo"
                        subtitle="Totais da correcção"
                        rows={[
                            { label: 'Subtotal', value: formatCurrency(toNumber(note.subtotal)) },
                            { label: 'Desconto', value: `-${formatCurrency(toNumber(note.discount_amount))}` },
                            { label: 'IVA', value: formatCurrency(toNumber(note.tax_amount)) },
                            { label: 'Total', value: formatCurrency(toNumber(note.total_amount)), emphasis: true },
                            { label: 'Valor aplicado', value: formatCurrency(toNumber(note.applied_amount)) },
                            { label: 'Saldo', value: formatCurrency(toNumber(note.balance_amount)) },
                        ]}
                    />
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <ReportPill tone="info">Crédito</ReportPill>
                    <ReportPill tone={documentStatus.tone}>{documentStatus.label}</ReportPill>
                    <ReportPill tone="success">IVA regularizado</ReportPill>
                </div>
            </div>
        </ReportShell>
    );
}
