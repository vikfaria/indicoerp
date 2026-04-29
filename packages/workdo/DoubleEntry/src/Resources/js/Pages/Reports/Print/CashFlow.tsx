import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface CashFlowData {
    beginning_cash: number;
    operating: number;
    investing: number;
    financing: number;
    net_cash_flow: number;
    ending_cash: number;
}

interface PrintProps {
    data: CashFlowData;
    filters: {
        from_date: string;
        to_date: string;
    };
}

export default function Print() {
    const { t } = useTranslation();
    const { data, filters } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.report-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `cash-flow-statement.pdf`,
                image: { type: 'jpeg' as const, quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' as const }
            };

            try {
                await html2pdf().set(opt).from(printContent as HTMLElement).save();
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    return (
        <div className="min-h-screen bg-white">
            <Head title={t('Cash Flow Statement')} />

            {isDownloading && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg shadow-lg">
                        <div className="flex items-center space-x-3">
                            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                            <p className="text-lg font-semibold text-gray-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="report-container bg-white max-w-5xl mx-auto p-8">
                <div className="border-b-2 border-gray-800 pb-6 mb-8">
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-2">{getCompanySetting('company_name') || 'YOUR COMPANY'}</h1>
                            <div className="text-sm text-gray-600 space-y-0.5">
                                {getCompanySetting('company_address') && <p>{getCompanySetting('company_address')}</p>}
                                {(getCompanySetting('company_city') || getCompanySetting('company_state') || getCompanySetting('company_zipcode')) && (
                                    <p>
                                        {getCompanySetting('company_city')}{getCompanySetting('company_state') && `, ${getCompanySetting('company_state')}`} {getCompanySetting('company_zipcode')}
                                    </p>
                                )}
                                {getCompanySetting('company_country') && <p>{getCompanySetting('company_country')}</p>}
                            </div>
                        </div>
                        <div className="text-right">
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('CASH FLOW STATEMENT')}</h2>
                            <p className="text-sm text-gray-600">{formatDate(filters.from_date)} {t('to')} {formatDate(filters.to_date)}</p>
                        </div>
                    </div>
                </div>

                <table className="w-full border-collapse">
                    <tbody>
                        <tr className="border-b-2 border-black page-break-inside-avoid">
                            <td className="py-3 text-sm font-bold">{t('Beginning Cash Balance')}</td>
                            <td className="py-3 text-sm font-bold text-right tabular-nums w-40">{formatCurrency(data.beginning_cash)}</td>
                        </tr>

                        <tr className="page-break-inside-avoid">
                            <td colSpan={2} className="pt-6 pb-2">
                                <h3 className="font-bold text-base">{t('Cash Flow from Operating Activities')}</h3>
                            </td>
                        </tr>
                        <tr className="page-break-inside-avoid">
                            <td className="py-2 pl-6 text-sm">{t('Net cash from operations')}</td>
                            <td className="py-2 text-sm text-right font-semibold tabular-nums">{formatCurrency(data.operating)}</td>
                        </tr>

                        <tr className="page-break-inside-avoid">
                            <td colSpan={2} className="pt-4 pb-2">
                                <h3 className="font-bold text-base">{t('Cash Flow from Investing Activities')}</h3>
                            </td>
                        </tr>
                        <tr className="page-break-inside-avoid">
                            <td className="py-2 pl-6 text-sm">{t('Net cash from investing')}</td>
                            <td className="py-2 text-sm text-right font-semibold tabular-nums">{formatCurrency(data.investing)}</td>
                        </tr>

                        <tr className="page-break-inside-avoid">
                            <td colSpan={2} className="pt-4 pb-2">
                                <h3 className="font-bold text-base">{t('Cash Flow from Financing Activities')}</h3>
                            </td>
                        </tr>
                        <tr className="page-break-inside-avoid">
                            <td className="py-2 pl-6 text-sm">{t('Net cash from financing')}</td>
                            <td className="py-2 text-sm text-right font-semibold tabular-nums">{formatCurrency(data.financing)}</td>
                        </tr>

                        <tr className="border-t-2 border-gray-400 page-break-inside-avoid">
                            <td className="py-3 text-sm font-bold">{t('Net Increase/Decrease in Cash')}</td>
                            <td className="py-3 text-sm font-bold text-right tabular-nums">{formatCurrency(data.net_cash_flow)}</td>
                        </tr>

                        <tr className="border-t-4 border-black page-break-inside-avoid">
                            <td className="py-4 text-base font-bold">{t('Ending Cash Balance')}</td>
                            <td className="py-4 text-base font-bold text-right tabular-nums">{formatCurrency(data.ending_cash)}</td>
                        </tr>
                    </tbody>
                </table>

                <div className="mt-8 pt-4 border-t text-center text-xs text-gray-600">
                    <p>{t('Generated on')} {formatDate(new Date().toISOString())}</p>
                </div>
            </div>

            <style>{`
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    font-family: Arial, sans-serif;
                }

                @page {
                    margin: 0.25in;
                    size: A4;
                }

                .report-container {
                    max-width: 100%;
                    margin: 0;
                    box-shadow: none;
                }

                .page-break-inside-avoid {
                    page-break-inside: avoid;
                    break-inside: avoid;
                }

                @media print {
                    body {
                        background: white;
                    }

                    .report-container {
                        box-shadow: none;
                    }

                    .page-break-inside-avoid {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }
                }
            `}</style>
        </div>
    );
}
