import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import { formatDate, getCompanySetting } from '@/utils/helpers';
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
import { Transfer } from './types';

interface ShowProps {
    transfer: Transfer & {
        creator?: {
            name?: string;
        } | null;
        [key: string]: any;
    };
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0);

export default function Show() {
    const page = usePage<ShowProps>();
    const { transfer } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);

    const transferData = transfer as ShowProps['transfer'];
    const companyName = getCompanySetting('company_name') || 'Empresa';
    const companyAddress = getCompanySetting('company_address');
    const companyCity = getCompanySetting('company_city');
    const companyState = getCompanySetting('company_state');
    const companyCountry = getCompanySetting('company_country');
    const companyZipcode = getCompanySetting('company_zipcode');
    const companyTelephone = getCompanySetting('company_telephone');
    const companyEmail = getCompanySetting('company_email');
    const companyTaxNumber = getCompanySetting('company_tax_number') || getCompanySetting('company_number');
    const totalQuantity = toNumber(transferData.quantity);
    const hasTransportDetails = Boolean(transferData.carrier_name || transferData.vehicle_plate || transferData.driver_name);
    const documentTitle = hasTransportDetails ? 'Guia de Transporte' : 'Guia de Remessa';
    const documentSubtitle = hasTransportDetails
        ? 'Documento de circulação com dados de transporte'
        : 'Documento de circulação interna de stock';

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
                filename: `transfer-${transferData.id}.pdf`,
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

    return (
        <ReportShell>
            <Head title={`${documentTitle} #${transferData.id}`} />

            {isDownloading && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="rounded-2xl bg-white px-6 py-5 shadow-xl">
                        <div className="flex items-center gap-3">
                            <div className="h-6 w-6 animate-spin rounded-full border-b-2 border-emerald-600" />
                            <p className="text-lg font-semibold text-slate-700">A gerar PDF...</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <Link
                    href={route('transfers.index')}
                    className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Voltar às transferências
                </Link>
                <div className="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <Printer className="h-4 w-4" />
                        Imprimir
                    </button>
                    <button
                        type="button"
                        onClick={downloadPDF}
                        className="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <Download className="h-4 w-4" />
                        Descarregar PDF
                    </button>
                </div>
            </div>

            <div className="document-print-container space-y-6">
                <ReportHero
                    title={documentTitle}
                    subtitle={documentSubtitle}
                    issuerTitle="Emitente"
                    issuerLines={[
                        companyName,
                        companyAddress,
                        [companyCity, companyState, companyZipcode].filter(Boolean).join(', '),
                        companyCountry,
                        companyTelephone ? `Telefone: ${companyTelephone}` : null,
                        companyEmail ? `E-mail: ${companyEmail}` : null,
                        companyTaxNumber ? `NUIT: ${companyTaxNumber}` : null,
                    ].filter(Boolean) as React.ReactNode[]}
                    documentLabel="Documento"
                    documentNumber={`#${transferData.id}`}
                    statusPills={[
                        { label: hasTransportDetails ? 'Guia de Transporte' : 'Guia de Remessa', tone: 'info' },
                        { label: 'Transferência', tone: 'success' },
                        { label: 'Pronto para arquivo', tone: 'neutral' },
                    ]}
                    meta={[
                        { label: 'Data', value: formatDate(transferData.date) },
                        { label: 'Quantidade', value: `${totalQuantity}` },
                        { label: 'Origem', value: transferData.from_warehouse?.name || '-' },
                        { label: 'Destino', value: transferData.to_warehouse?.name || '-' },
                        { label: 'Transportador', value: transferData.carrier_name || 'Não registado' },
                        { label: 'Matrícula', value: transferData.vehicle_plate || 'Não registada' },
                    ]}
                    note={hasTransportDetails
                        ? 'Documento de circulação com dados de transporte completos.'
                        : 'Documento de circulação interna. Preencha transportador, matrícula e motorista para emitir uma guia de transporte completa.'}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <ReportCard title="Origem e destino" subtitle="Locais de carga e descarga">
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Emitente', value: companyName },
                                { label: 'Destinatário', value: transferData.to_warehouse?.name || '-' },
                                { label: 'Local de carga', value: transferData.from_warehouse?.name || '-' },
                                { label: 'Local de descarga', value: transferData.to_warehouse?.name || '-' },
                                { label: 'Responsável', value: transferData.creator?.name || '-' },
                                { label: 'Documento relacionado', value: `Transferência #${transferData.id}` },
                            ]}
                        />
                    </ReportCard>

                    <ReportSummaryCard
                        title="Resumo"
                        subtitle="Dados operacionais"
                        rows={[
                            { label: 'Quantidade total', value: `${totalQuantity}` },
                            { label: 'Motivo', value: 'Transferência interna de stock' },
                            { label: 'Tipo', value: documentTitle, emphasis: true },
                            { label: 'Estado', value: 'Concluída' },
                        ]}
                    />
                </div>

                <ReportTable headers={['Produto', 'SKU', 'Quantidade', 'Carga', 'Descarga']}>
                    <tr className="report-page-break-inside-avoid">
                        <td className="px-4 py-4 align-top">
                            <div className="font-semibold text-slate-900">{transferData.product?.name || '-'}</div>
                        </td>
                        <td className="px-4 py-4 align-top tabular-nums">{transferData.product?.sku || '-'}</td>
                        <td className="px-4 py-4 text-right align-top tabular-nums">{transferData.quantity}</td>
                        <td className="px-4 py-4 align-top">{transferData.from_warehouse?.name || '-'}</td>
                        <td className="px-4 py-4 align-top">{transferData.to_warehouse?.name || '-'}</td>
                    </tr>
                </ReportTable>

                <div className="grid gap-6 lg:grid-cols-2">
                    <ReportCard title="Transporte" subtitle={hasTransportDetails ? 'Dados de transporte registados' : 'Dados a completar quando aplicável'}>
                        <ReportKeyValueGrid
                            columns={2}
                            items={[
                                { label: 'Transportador', value: transferData.carrier_name || 'Não registado' },
                                { label: 'Matrícula', value: transferData.vehicle_plate || 'Não registada' },
                                { label: 'Motorista', value: transferData.driver_name || 'Não registado' },
                                { label: 'Observações', value: hasTransportDetails ? 'Documento apto para circulação com transportador identificado.' : 'Transferência interna sem dados de transporte externos.', span: 2 },
                            ]}
                        />
                    </ReportCard>

                    <ReportCard title="Notas" subtitle="Apoio ao arquivo">
                        <div className="space-y-3 text-sm leading-6 text-slate-700">
                            <p>Este documento organiza a circulação entre armazéns e serve como base operacional para a remessa interna.</p>
                            <p>Se precisar de uma Guia de Transporte legalmente completa, o sistema deve recolher transportador, matrícula e motorista no fluxo de registo.</p>
                        </div>
                    </ReportCard>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <ReportPill tone="info">{documentTitle}</ReportPill>
                    <ReportPill tone="success">Transferência interna</ReportPill>
                    <ReportPill tone={hasTransportDetails ? 'success' : 'neutral'}>
                        {hasTransportDetails ? 'Dados de transporte completos' : 'Sem dados de transporte'}
                    </ReportPill>
                </div>
            </div>
        </ReportShell>
    );
}
