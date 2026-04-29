import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

export default function Print() {
    const { t } = useTranslation();
    const { data, filters } = usePage<any>().props;
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
                filename: `tax-summary-${filters.from_date}-to-${filters.to_date}.pdf`,
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
            <Head title={t('Tax Summary Report')} />
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
                            </div>
                        </div>
                        <div className="text-right">
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('TAX SUMMARY REPORT')}</h2>
                            <p className="text-sm text-gray-600">{formatDate(filters.from_date)} {t('to')} {formatDate(filters.to_date)}</p>
                        </div>
                    </div>
                </div>
                <table className="w-full border-collapse">
                    <tbody>
                        <tr className="border-b-2 border-black">
                            <td className="py-3 px-2 font-semibold text-sm">{t('Tax Collected (Sales)')}</td>
                            <td className="py-3 px-2 text-right"></td>
                        </tr>
                        {data.tax_collected.items.map((item: any, idx: number) => (
                            <tr key={idx} className="border-b border-gray-200">
                                <td className="py-2 px-6 text-sm">{item.tax_name}</td>
                                <td className="py-2 px-2 text-sm text-right">{formatCurrency(item.amount)}</td>
                            </tr>
                        ))}
                        <tr className="font-semibold border-t-2 border-black">
                            <td className="py-3 px-2 text-sm">{t('Total Tax Collected')}</td>
                            <td className="py-3 px-2 text-sm text-right">{formatCurrency(data.tax_collected.total)}</td>
                        </tr>
                        <tr className="border-b-2 border-black">
                            <td className="py-3 px-2 font-semibold text-sm">{t('Tax Paid (Purchases)')}</td>
                            <td className="py-3 px-2 text-right"></td>
                        </tr>
                        {data.tax_paid.items.map((item: any, idx: number) => (
                            <tr key={idx} className="border-b border-gray-200">
                                <td className="py-2 px-6 text-sm">{item.tax_name}</td>
                                <td className="py-2 px-2 text-sm text-right">{formatCurrency(item.amount)}</td>
                            </tr>
                        ))}
                        <tr className="font-semibold border-t-2 border-black">
                            <td className="py-3 px-2 text-sm">{t('Total Tax Paid')}</td>
                            <td className="py-3 px-2 text-sm text-right">{formatCurrency(data.tax_paid.total)}</td>
                        </tr>
                        <tr className="font-bold border-t-4 border-black">
                            <td className="py-4 px-2 text-base">{t('Net Tax Liability')}</td>
                            <td className="py-4 px-2 text-base text-right">{formatCurrency(data.net_tax_liability)}</td>
                        </tr>
                    </tbody>
                </table>
                <div className="mt-8 pt-4 border-t text-center text-xs text-gray-600">
                    <p>{t('Generated on')} {formatDate(new Date().toISOString())}</p>
                </div>
            </div>
        </div>
    );
}
