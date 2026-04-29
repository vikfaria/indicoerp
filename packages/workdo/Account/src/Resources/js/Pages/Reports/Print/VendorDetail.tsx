import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

export default function Print() {
    const { t } = useTranslation();
    const { data, filters } = usePage<any>().props;
    const [isDownloading, setIsDownloading] = useState(false);
    const vendorDisplayName = data.vendor.company_name || data.vendor.name;
    const showVendorPersonName = Boolean(data.vendor.company_name && data.vendor.company_name !== data.vendor.name);
    const vendorTaxLabel = data.vendor.tax_label || t('Tax Number');

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
                filename: `vendor-detail-${data.vendor.name}-${filters.start_date || 'all'}.pdf`,
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
            <Head title={t('Vendor Detail Report')} />
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
            <div className="report-container bg-white max-w-7xl mx-auto p-8">
                <div className="border-b-2 border-gray-800 pb-6 mb-8">
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-2">{getCompanySetting('company_name') || 'YOUR COMPANY'}</h1>
                            <div className="text-sm text-gray-600 space-y-0.5">
                                {getCompanySetting('company_address') && <p>{getCompanySetting('company_address')}</p>}
                            </div>
                        </div>
                        <div className="text-right">
                            <h2 className="text-2xl font-bold text-gray-900 mb-3">{t('VENDOR DETAIL REPORT')}</h2>
                            <p className="text-sm text-gray-600">{t('Vendor')}: {vendorDisplayName}</p>
                            {showVendorPersonName && (
                                <p className="text-sm text-gray-600">{data.vendor.name}</p>
                            )}
                            {data.vendor.tax_number && (
                                <p className="text-sm text-gray-600">{vendorTaxLabel}: {data.vendor.tax_number}</p>
                            )}
                            {filters.start_date && <p className="text-sm text-gray-600">{t('Period')}: {formatDate(filters.start_date)} - {formatDate(filters.end_date)}</p>}
                        </div>
                    </div>
                </div>

                <div className="mb-6">
                    <h3 className="text-lg font-bold border-b-2 border-black pb-2 mb-3">{t('PURCHASE INVOICES')}</h3>
                    <table className="w-full border-collapse mb-4">
                        <thead>
                            <tr className="border-b-2 border-black">
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Invoice Number')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Date')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Due Date')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Subtotal')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Tax')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Total')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Balance')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.invoices.map((invoice: any, idx: number) => (
                                <tr key={idx} className="border-b border-gray-200">
                                    <td className="py-2 px-2 text-sm">{invoice.invoice_number}</td>
                                    <td className="py-2 px-2 text-sm">{formatDate(invoice.date)}</td>
                                    <td className="py-2 px-2 text-sm">{formatDate(invoice.due_date)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(invoice.subtotal)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(invoice.tax_amount)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(invoice.total_amount)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-semibold">{formatCurrency(invoice.balance_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mb-6">
                    <h3 className="text-lg font-bold border-b-2 border-black pb-2 mb-3">{t('PURCHASE RETURNS')}</h3>
                    <table className="w-full border-collapse mb-4">
                        <thead>
                            <tr className="border-b-2 border-black">
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Return Number')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Date')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Subtotal')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Tax')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Total')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.returns.map((ret: any, idx: number) => (
                                <tr key={idx} className="border-b border-gray-200">
                                    <td className="py-2 px-2 text-sm">{ret.return_number}</td>
                                    <td className="py-2 px-2 text-sm">{formatDate(ret.date)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(ret.subtotal)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(ret.tax_amount)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-semibold">{formatCurrency(ret.total_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mb-6">
                    <h3 className="text-lg font-bold border-b-2 border-black pb-2 mb-3">{t('DEBIT NOTES')}</h3>
                    <table className="w-full border-collapse mb-4">
                        <thead>
                            <tr className="border-b-2 border-black">
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Debit Note Number')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Date')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Total')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Applied')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Balance')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.debit_notes.map((note: any, idx: number) => (
                                <tr key={idx} className="border-b border-gray-200">
                                    <td className="py-2 px-2 text-sm">{note.debit_note_number}</td>
                                    <td className="py-2 px-2 text-sm">{formatDate(note.date)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(note.total_amount)}</td>
                                    <td className="py-2 px-2 text-sm text-right">{formatCurrency(note.applied_amount)}</td>
                                    <td className="py-2 px-2 text-sm text-right font-semibold">{formatCurrency(note.balance_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mb-6">
                    <h3 className="text-lg font-bold border-b-2 border-black pb-2 mb-3">{t('VENDOR PAYMENTS')}</h3>
                    <table className="w-full border-collapse mb-4">
                        <thead>
                            <tr className="border-b-2 border-black">
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Payment Number')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Date')}</th>
                                <th className="text-left py-2 px-2 text-sm font-semibold">{t('Bank Account')}</th>
                                <th className="text-right py-2 px-2 text-sm font-semibold">{t('Amount')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.payments.map((payment: any, idx: number) => (
                                <tr key={idx} className="border-b border-gray-200">
                                    <td className="py-2 px-2 text-sm">{payment.payment_number}</td>
                                    <td className="py-2 px-2 text-sm">{formatDate(payment.date)}</td>
                                    <td className="py-2 px-2 text-sm">{payment.bank_account || '-'}</td>
                                    <td className="py-2 px-2 text-sm text-right font-semibold">{formatCurrency(payment.amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="border-t-2 border-black pt-4 mt-8">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div><span className="font-semibold">{t('Total Invoiced')}:</span> {formatCurrency(data.summary.total_invoiced)}</div>
                        <div><span className="font-semibold">{t('Total Returns')}:</span> {formatCurrency(data.summary.total_returns)}</div>
                        <div><span className="font-semibold">{t('Total Debit Notes')}:</span> {formatCurrency(data.summary.total_debit_notes)}</div>
                        <div><span className="font-semibold">{t('Total Payments')}:</span> {formatCurrency(data.summary.total_payments)}</div>
                        <div className="col-span-2 text-lg"><span className="font-bold">{t('Balance')}:</span> <span className="font-bold">{formatCurrency(data.summary.balance)}</span></div>
                    </div>
                </div>

                <div className="mt-8 pt-4 border-t text-center text-xs text-gray-600">
                    <p>{t('Generated on')} {formatDate(new Date().toISOString())}</p>
                </div>
            </div>
        </div>
    );
}
