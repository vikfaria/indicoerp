import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface AccountBalanceItem {
    account_code: string;
    account_name: string;
    debit: number;
    credit: number;
    net_balance: number;
}

interface AccountBalanceGroup {
    accounts: AccountBalanceItem[];
    subtotal_debit: number;
    subtotal_credit: number;
    subtotal_net: number;
}

interface AccountBalanceData {
    grouped: Record<string, AccountBalanceGroup>;
    totals: {
        debit: number;
        credit: number;
        net: number;
    };
    as_of_date: string;
}

interface PrintProps {
    data: AccountBalanceData;
    filters: {
        as_of_date: string;
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
                filename: `account-balance-summary.pdf`,
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
            <Head title={t('Account Balance Summary')} />

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
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('ACCOUNT BALANCE SUMMARY')}</h2>
                            <p className="text-sm text-gray-600">{t('As of')}: {formatDate(filters.as_of_date)}</p>
                        </div>
                    </div>
                </div>

                {Object.entries(data.grouped).map(([type, group]) => (
                    <div key={type} className="mb-6 page-break-inside-avoid">
                        <h3 className="text-base font-bold border-b-2 border-gray-400 pb-1 mb-2">{t(type)}</h3>
                        <table className="w-full border-collapse mb-4 page-break-inside-avoid">
                            <thead>
                                <tr className="border-b-2 border-black">
                                    <th className="text-left py-2 px-2 text-sm font-semibold w-24">{t('Account Code')}</th>
                                    <th className="text-left py-2 px-2 text-sm font-semibold">{t('Account Name')}</th>
                                    <th className="text-right py-2 px-2 text-sm font-semibold w-28">{t('Debit')}</th>
                                    <th className="text-right py-2 px-2 text-sm font-semibold w-28">{t('Credit')}</th>
                                    <th className="text-right py-2 px-2 text-sm font-semibold w-32">{t('Net Balance')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {group.accounts.map((account, idx) => (
                                    <tr key={idx} className="border-b border-gray-200">
                                        <td className="py-2 px-2 text-sm">{account.account_code}</td>
                                        <td className="py-2 px-2 text-sm break-words">{account.account_name}</td>
                                        <td className="py-2 px-2 text-sm text-right tabular-nums">
                                            {account.debit > 0 ? formatCurrency(account.debit) : '-'}
                                        </td>
                                        <td className="py-2 px-2 text-sm text-right tabular-nums">
                                            {account.credit > 0 ? formatCurrency(account.credit) : '-'}
                                        </td>
                                        <td className="py-2 px-2 text-sm text-right font-medium tabular-nums">
                                            {formatCurrency(account.net_balance)}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t-2 border-gray-400">
                                    <td colSpan={2} className="py-2 px-2 text-sm font-bold">{t('Subtotal')} - {t(type)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-bold tabular-nums">{formatCurrency(group.subtotal_debit)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-bold tabular-nums">{formatCurrency(group.subtotal_credit)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-bold tabular-nums">{formatCurrency(group.subtotal_net)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                ))}

                <table className="w-full border-collapse border-t-4 border-black">
                    <tbody>
                        <tr className="font-bold">
                            <td colSpan={2} className="py-3 px-2 text-sm">{t('GRAND TOTAL')}</td>
                            <td className="py-3 px-2 text-sm text-right tabular-nums w-28">{formatCurrency(data.totals.debit)}</td>
                            <td className="py-3 px-2 text-sm text-right tabular-nums w-28">{formatCurrency(data.totals.credit)}</td>
                            <td className="py-3 px-2 text-sm text-right tabular-nums w-32">{formatCurrency(data.totals.net)}</td>
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
