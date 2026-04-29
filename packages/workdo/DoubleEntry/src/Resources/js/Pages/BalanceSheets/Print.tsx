import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';
import { BalanceSheetViewProps } from './types';

export default function Print() {
    const { t } = useTranslation();
    const { balanceSheet, groupedItems } = usePage<BalanceSheetViewProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    // Calculate totals from actual items
    const totalEquity = groupedItems.equity ? Object.values(groupedItems.equity).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0;
    const totalLiabilities = groupedItems.liabilities ? Object.values(groupedItems.liabilities).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0;
    const totalAssets = groupedItems.assets ? Object.values(groupedItems.assets).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0;

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.balance-sheet-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `balance-sheet-${formatDate(balanceSheet.balance_sheet_date)}.pdf`,
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
            <Head title={t('Balance Sheet')} />

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

            <div className="balance-sheet-container bg-white max-w-4xl mx-auto p-12">
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
                        <h2 className="text-2xl font-bold mb-2">{t('BALANCE SHEET')}</h2>
                        <div className="text-sm space-y-1">
                            <p>{t('As of')}: {formatDate(balanceSheet.balance_sheet_date)}</p>
                            <p>{t('Financial Year')}: {balanceSheet.financial_year}</p>
                        </div>
                    </div>
                </div>

                {/* Two Column Layout */}
                <div className="grid grid-cols-2 gap-8 mb-6">
                    {/* Left Column - Liabilities & Equity */}
                    <div>
                        <h3 className="text-base font-bold border-b-2 border-gray-800 pb-2 mb-3">{t('Liabilities & Equity')}</h3>
                        
                        {/* Equity */}
                        {groupedItems.equity && (
                            <div className="mb-4">
                                <h4 className="font-semibold text-sm mb-2">{t('Equity')}</h4>
                                {Object.entries(groupedItems.equity).map(([subSection, items]) => (
                                    <div key={subSection}>
                                        {items.map((item) => (
                                            <div key={item.id} className="flex justify-between py-1.5 text-sm">
                                                <span>{item.account?.account_name}</span>
                                                <span className="tabular-nums">{formatCurrency(item.amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                ))}
                                <div className="flex justify-between py-2 font-semibold text-sm border-t mt-2">
                                    <span>{t('Total Equity')}</span>
                                    <span className="tabular-nums">{formatCurrency(totalEquity)}</span>
                                </div>
                            </div>
                        )}

                        {/* Liabilities */}
                        {groupedItems.liabilities && (
                            <div className="mb-4">
                                <h4 className="font-semibold text-sm mb-2">{t('Liabilities')}</h4>
                                {Object.entries(groupedItems.liabilities).map(([subSection, items]) => (
                                    <div key={subSection} className="mb-3">
                                        <h5 className="font-medium text-xs capitalize mb-1">{subSection.replace('_', ' ')}</h5>
                                        {items.map((item) => (
                                            <div key={item.id} className="flex justify-between py-1.5 text-sm ml-3">
                                                <span>{item.account?.account_name}</span>
                                                <span className="tabular-nums">{formatCurrency(item.amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                ))}
                                <div className="flex justify-between py-2 font-semibold text-sm border-t mt-2">
                                    <span>{t('Total Liabilities')}</span>
                                    <span className="tabular-nums">{formatCurrency(totalLiabilities)}</span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Column - Assets */}
                    <div>
                        <h3 className="text-base font-bold border-b-2 border-gray-800 pb-2 mb-3">{t('Assets')}</h3>
                        
                        {groupedItems.assets && (
                            <div>
                                {Object.entries(groupedItems.assets).map(([subSection, items]) => (
                                    <div key={subSection} className="mb-3">
                                        <h4 className="font-medium text-xs capitalize mb-1">{subSection.replace('_', ' ')}</h4>
                                        {items.map((item) => (
                                            <div key={item.id} className="flex justify-between py-1.5 text-sm ml-3">
                                                <span>{item.account?.account_name}</span>
                                                <span className="tabular-nums">{formatCurrency(item.amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Totals Row */}
                <div className="grid grid-cols-2 gap-8 border-t-2 border-gray-800 pt-4">
                    <div className="flex justify-between font-bold text-base">
                        <span>{t('Total Liabilities & Equity')}</span>
                        <span className="tabular-nums">{formatCurrency(totalLiabilities + totalEquity)}</span>
                    </div>
                    <div className="flex justify-between font-bold text-base">
                        <span>{t('Total Assets')}</span>
                        <span className="tabular-nums">{formatCurrency(totalAssets)}</span>
                    </div>
                </div>

                {/* Footer */}
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

                .balance-sheet-container {
                    max-width: 100%;
                    margin: 0;
                    box-shadow: none;
                }

                @media print {
                    body {
                        background: white;
                    }

                    .balance-sheet-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}
