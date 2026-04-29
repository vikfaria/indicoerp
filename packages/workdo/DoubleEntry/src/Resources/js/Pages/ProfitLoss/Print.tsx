import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface Account {
    id: number;
    account_code: string;
    account_name: string;
    balance: number;
}

interface ProfitLossData {
    revenue: Account[];
    expenses: Account[];
    total_revenue: number;
    total_expenses: number;
    net_profit: number;
    from_date: string;
    to_date: string;
}

interface ProfitLossProps {
    profitLoss: ProfitLossData;
}

export default function Print() {
    const { t } = useTranslation();
    const { profitLoss } = usePage<ProfitLossProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.profit-loss-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `profit-loss-${formatDate(profitLoss.from_date)}-to-${formatDate(profitLoss.to_date)}.pdf`,
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
            <Head title={t('Profit & Loss Statement')} />

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

            <div className="profit-loss-container bg-white max-w-4xl mx-auto p-12">
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
                        <h2 className="text-2xl font-bold mb-2">{t('PROFIT & LOSS STATEMENT')}</h2>
                        <div className="text-sm space-y-1">
                            <p>{t('Period')}: {formatDate(profitLoss.from_date)} - {formatDate(profitLoss.to_date)}</p>
                        </div>
                    </div>
                </div>

                {/* Two Column Layout */}
                <div className="grid grid-cols-2 gap-8 mb-6">
                    {/* Left Column - Revenue */}
                    <div>
                        <h3 className="text-base font-bold border-b-2 border-gray-800 pb-2 mb-3">{t('Revenue')}</h3>
                        {profitLoss.revenue.length > 0 ? (
                            profitLoss.revenue.map((account) => (
                                <div key={account.id} className="flex justify-between py-1.5 text-sm">
                                    <span>{account.account_code} - {account.account_name}</span>
                                    <span className="tabular-nums">{formatCurrency(account.balance)}</span>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm py-2">{t('No revenue accounts')}</p>
                        )}
                        <div className="flex justify-between py-2 font-semibold text-sm border-t mt-2">
                            <span>{t('Total Revenue')}</span>
                            <span className="tabular-nums">{formatCurrency(profitLoss.total_revenue)}</span>
                        </div>
                    </div>

                    {/* Right Column - Expenses */}
                    <div>
                        <h3 className="text-base font-bold border-b-2 border-gray-800 pb-2 mb-3">{t('Expenses')}</h3>
                        {profitLoss.expenses.length > 0 ? (
                            profitLoss.expenses.map((account) => (
                                <div key={account.id} className="flex justify-between py-1.5 text-sm">
                                    <span>{account.account_code} - {account.account_name}</span>
                                    <span className="tabular-nums">{formatCurrency(account.balance)}</span>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm py-2">{t('No expense accounts')}</p>
                        )}
                        <div className="flex justify-between py-2 font-semibold text-sm border-t mt-2">
                            <span>{t('Total Expenses')}</span>
                            <span className="tabular-nums">{formatCurrency(profitLoss.total_expenses)}</span>
                        </div>
                    </div>
                </div>

                {/* Net Profit/Loss */}
                <div className="mt-8 pt-4 border-t-2 border-gray-800">
                    <div className="flex justify-between py-2 font-bold text-base">
                        <span>{profitLoss.net_profit >= 0 ? t('Net Profit') : t('Net Loss')}</span>
                        <span className="tabular-nums">
                            {formatCurrency(Math.abs(profitLoss.net_profit))}
                        </span>
                    </div>
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

                .profit-loss-container {
                    max-width: 100%;
                    margin: 0;
                    box-shadow: none;
                }

                @media print {
                    body {
                        background: white;
                    }

                    .profit-loss-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}
