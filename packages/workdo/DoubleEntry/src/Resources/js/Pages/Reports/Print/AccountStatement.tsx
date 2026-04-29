import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface Transaction {
    id: number;
    date: string;
    description: string;
    reference_type: string;
    debit: number;
    credit: number;
    balance: number;
}

interface AccountStatementData {
    opening_balance: number;
    transactions: Transaction[];
    closing_balance: number;
}

interface Account {
    account_code: string;
    account_name: string;
}

interface PrintProps {
    data: AccountStatementData;
    selectedAccount: Account | null;
    filters: {
        from_date: string;
        to_date: string;
    };
}

export default function Print() {
    const { t } = useTranslation();
    const { data, selectedAccount, filters } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    const totalDebit = data.transactions.reduce((sum, t) => sum + t.debit, 0);
    const totalCredit = data.transactions.reduce((sum, t) => sum + t.credit, 0);

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
                filename: `account-statement-${selectedAccount?.account_code || 'report'}.pdf`,
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
            <Head title={t('Account Statement')} />

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
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('ACCOUNT STATEMENT')}</h2>
                            {selectedAccount && (
                                <div className="text-sm text-gray-700 space-y-1">
                                    <p className="font-semibold text-base">{selectedAccount.account_code} - {selectedAccount.account_name}</p>
                                    {filters.from_date && filters.to_date && (
                                        <p className="text-gray-600">{formatDate(filters.from_date)} {t('to')} {formatDate(filters.to_date)}</p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <table className="w-full border-collapse">
                    <thead>
                        <tr className="border-b-2 border-black">
                            <th className="text-left py-2 px-3 text-sm font-semibold w-24">{t('Date')}</th>
                            <th className="text-left py-2 px-3 text-sm font-semibold">{t('Description')}</th>
                            <th className="text-left py-2 px-3 text-sm font-semibold w-28">{t('Reference')}</th>
                            <th className="text-right py-2 px-3 text-sm font-semibold w-24">{t('Debit')}</th>
                            <th className="text-right py-2 px-3 text-sm font-semibold w-24">{t('Credit')}</th>
                            <th className="text-right py-2 px-3 text-sm font-semibold w-28">{t('Balance')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.opening_balance !== 0 && (
                            <tr className="border-b border-gray-300">
                                <td colSpan={5} className="py-2 px-3 text-sm font-semibold">{t('Opening Balance')}</td>
                                <td className="py-2 px-3 text-sm text-right font-semibold tabular-nums">
                                    {formatCurrency(data.opening_balance)}
                                </td>
                            </tr>
                        )}
                        {data.transactions.map((transaction) => (
                            <tr key={transaction.id} className="border-b border-gray-200 page-break-inside-avoid">
                                <td className="py-2 px-3 text-sm whitespace-nowrap">{formatDate(transaction.date)}</td>
                                <td className="py-2 px-3 text-sm break-words">{transaction.description}</td>
                                <td className="py-2 px-3 text-sm">{transaction.reference_type}</td>
                                <td className="py-2 px-3 text-sm text-right tabular-nums">
                                    {transaction.debit > 0 ? formatCurrency(transaction.debit) : '-'}
                                </td>
                                <td className="py-2 px-3 text-sm text-right tabular-nums">
                                    {transaction.credit > 0 ? formatCurrency(transaction.credit) : '-'}
                                </td>
                                <td className="py-2 px-3 text-sm text-right font-medium tabular-nums">
                                    {formatCurrency(transaction.balance)}
                                </td>
                            </tr>
                        ))}
                        <tr className="border-t-2 border-gray-400">
                            <td colSpan={3} className="py-2 px-3 text-sm font-bold">{t('Total')}</td>
                            <td className="py-2 px-3 text-sm text-right font-bold tabular-nums">
                                {formatCurrency(totalDebit)}
                            </td>
                            <td className="py-2 px-3 text-sm text-right font-bold tabular-nums">
                                {formatCurrency(totalCredit)}
                            </td>
                            <td className="py-2 px-3 text-sm"></td>
                        </tr>
                        <tr className="border-t-2 border-black">
                            <td colSpan={5} className="py-2 px-3 text-sm font-bold">{t('Closing Balance')}</td>
                            <td className="py-2 px-3 text-sm text-right font-bold tabular-nums">
                                {formatCurrency(data.closing_balance)}
                            </td>
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
