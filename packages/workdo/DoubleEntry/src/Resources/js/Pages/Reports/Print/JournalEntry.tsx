import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface JournalEntryItem {
    account_code: string;
    account_name: string;
    description: string;
    debit: number;
    credit: number;
}

interface JournalEntryData {
    id: number;
    journal_number: string;
    date: string;
    reference_type: string;
    description: string;
    total_debit: number;
    total_credit: number;
    status: string;
    is_balanced: boolean;
    items: JournalEntryItem[];
}

interface PrintProps {
    data: JournalEntryData[];
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
                filename: `journal-entry-report.pdf`,
                image: { type: 'jpeg' as const, quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' as const }
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
            <Head title={t('Journal Entry Report')} />

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

            <div className="report-container bg-white max-w-6xl mx-auto p-8">
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
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('JOURNAL ENTRY REPORT')}</h2>
                            {filters.from_date && filters.to_date && (
                                <p className="text-sm text-gray-600">{formatDate(filters.from_date)} {t('to')} {formatDate(filters.to_date)}</p>
                            )}
                        </div>
                    </div>
                </div>

                {data.map((entry) => (
                    <div key={entry.id} className="mb-6 border border-gray-300 p-4 page-break-inside-avoid">
                        <div className="flex justify-between mb-3 pb-2 border-b border-gray-200">
                            <div>
                                <p className="font-bold text-base">{entry.journal_number}</p>
                                <p className="text-sm text-gray-600">{formatDate(entry.date)} | {entry.reference_type}</p>
                                <p className="text-sm text-gray-700">{entry.description}</p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-semibold">{entry.status === 'posted' ? t('Posted') : t('Draft')}</p>
                                {!entry.is_balanced && <p className="text-sm text-red-600 font-semibold">{t('Unbalanced')}</p>}
                            </div>
                        </div>
                        <table className="w-full border-collapse">
                            <thead>
                                <tr className="border-b-2 border-black">
                                    <th className="text-left py-2 px-2 text-sm font-semibold w-24">{t('Account Code')}</th>
                                    <th className="text-left py-2 px-2 text-sm font-semibold w-48">{t('Account Name')}</th>
                                    <th className="text-left py-2 px-2 text-sm font-semibold">{t('Description')}</th>
                                    <th className="text-right py-2 px-2 text-sm font-semibold w-28">{t('Debit')}</th>
                                    <th className="text-right py-2 px-2 text-sm font-semibold w-28">{t('Credit')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {entry.items.map((item, idx) => (
                                    <tr key={idx} className="border-b border-gray-200">
                                        <td className="py-2 px-2 text-sm">{item.account_code}</td>
                                        <td className="py-2 px-2 text-sm">{item.account_name}</td>
                                        <td className="py-2 px-2 text-sm break-words">{item.description}</td>
                                        <td className="py-2 px-2 text-sm text-right tabular-nums">
                                            {item.debit > 0 ? formatCurrency(item.debit) : '-'}
                                        </td>
                                        <td className="py-2 px-2 text-sm text-right tabular-nums">
                                            {item.credit > 0 ? formatCurrency(item.credit) : '-'}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t-2 border-black">
                                    <td colSpan={3} className="py-2 px-2 text-sm font-bold">{t('Total')}</td>
                                    <td className="py-2 px-2 text-sm text-right font-bold tabular-nums">{formatCurrency(entry.total_debit)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-bold tabular-nums">{formatCurrency(entry.total_credit)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                ))}

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
                    size: A4 landscape;
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
