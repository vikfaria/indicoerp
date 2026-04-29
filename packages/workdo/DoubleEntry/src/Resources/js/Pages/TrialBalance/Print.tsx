import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface TrialBalanceAccount {
    id: number;
    account_code: string;
    account_name: string;
    debit: number;
    credit: number;
}

interface TrialBalanceData {
    accounts: TrialBalanceAccount[];
    total_debit: number;
    total_credit: number;
    is_balanced: boolean;
    from_date: string;
    to_date: string;
}

interface TrialBalanceProps {
    trialBalance: TrialBalanceData;
}

export default function Print() {
    const { t } = useTranslation();
    const { trialBalance } = usePage<TrialBalanceProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.trial-balance-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `trial-balance-${formatDate(trialBalance.from_date)}-to-${formatDate(trialBalance.to_date)}.pdf`,
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
            <Head title={t('Trial Balance')} />

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

            <div className="trial-balance-container bg-white max-w-4xl mx-auto p-12">
                {/* Header */}
                <div className="flex justify-between items-start mb-12">
                    <div>
                        <h1 className="text-2xl font-bold mb-4">{getCompanySetting('company_name') || 'YOUR COMPANY'}</h1>
                        <div className="text-sm space-y-1">
                            {getCompanySetting('company_address') && <p>{getCompanySetting('company_address')}</p>}
                            {(getCompanySetting('company_city') || getCompanySetting('company_state') || getCompanySetting('company_zipcode')) && (
                                <p>
                                    {getCompanySetting('company_city')}{getCompanySetting('company_state') && `, ${getCompanySetting('company_state')}`} {getCompanySetting('company_zipcode')}
                                </p>
                            )}
                            {getCompanySetting('company_country') && <p>{getCompanySetting('company_country')}</p>}
                            {getCompanySetting('company_telephone') && <p>{t('Phone')}: {getCompanySetting('company_telephone')}</p>}
                            {getCompanySetting('company_email') && <p>{t('Email')}: {getCompanySetting('company_email')}</p>}
                        </div>
                    </div>
                    <div className="text-right">
                        <h2 className="text-2xl font-bold mb-2">{t('TRIAL BALANCE')}</h2>
                        <div className="text-sm space-y-1">
                            <p>{t('Period')}: {formatDate(trialBalance.from_date)} - {formatDate(trialBalance.to_date)}</p>
                        </div>
                    </div>
                </div>

                {/* Trial Balance Table */}
                <div className="mb-6">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b-2 border-gray-800">
                                <th className="text-left py-2 text-sm font-bold">{t('Account Code')}</th>
                                <th className="text-left py-2 text-sm font-bold">{t('Account Name')}</th>
                                <th className="text-right py-2 text-sm font-bold">{t('Debit')}</th>
                                <th className="text-right py-2 text-sm font-bold">{t('Credit')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {trialBalance.accounts.map((account) => (
                                <tr key={account.id} className="border-b border-gray-100">
                                    <td className="py-1.5 text-sm">{account.account_code}</td>
                                    <td className="py-1.5 text-sm">{account.account_name}</td>
                                    <td className="py-1.5 text-sm text-right tabular-nums">
                                        {account.debit > 0 ? formatCurrency(account.debit) : '-'}
                                    </td>
                                    <td className="py-1.5 text-sm text-right tabular-nums">
                                        {account.credit > 0 ? formatCurrency(account.credit) : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-gray-800">
                                <td colSpan={2} className="py-2 text-sm font-bold">{t('TOTAL')}</td>
                                <td className="py-2 text-sm text-right font-bold tabular-nums">{formatCurrency(trialBalance.total_debit)}</td>
                                <td className="py-2 text-sm text-right font-bold tabular-nums">{formatCurrency(trialBalance.total_credit)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {/* Footer */}
                <div className="mt-12 pt-6 border-t text-center text-sm text-gray-600">
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

                .trial-balance-container {
                    max-width: 100%;
                    margin: 0;
                    box-shadow: none;
                }

                @media print {
                    body {
                        background: white;
                    }

                    .trial-balance-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}
