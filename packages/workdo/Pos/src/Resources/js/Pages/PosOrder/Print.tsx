import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting } from '@/utils/helpers';

interface PosItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: {
        id: number;
        name: string;
        sku?: string;
    };
}

interface PosSale {
    id: number;
    sale_number: string;
    customer?: {
        name: string;
        email?: string;
    };
    warehouse?: {
        name: string;
    };
    subtotal: number;
    discount_amount: number;
    tax_amount?: number;
    total: number;
    created_at: string;
    items: PosItem[];
}

interface PrintProps {
    sale: PosSale;
}

export default function Print() {
    const { t } = useTranslation();
    const { sale } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.sale-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `pos-sale-${sale.sale_number}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            try {
                await html2pdf().set(opt).from(printContent).save();
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    return (
        <div className="min-h-screen bg-white">
            <Head title={t('POS Sale')} />

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

            <div className="sale-container bg-white max-w-4xl mx-auto p-12">
                {/* Header */}
                <div className="flex justify-between items-start mb-12">
                    <div className="w-1/2">
                        <h1 className="text-2xl font-bold mb-4">{getCompanySetting('company_name') || 'YOUR COMPANY'}</h1>
                        <div className="text-sm space-y-1">
                            {getCompanySetting('company_address') && <p>{getCompanySetting('company_address')}</p>}
                            {(getCompanySetting('company_city') || getCompanySetting('company_state')) && (
                                <p>{getCompanySetting('company_city')}, {getCompanySetting('company_state')}</p>
                            )}
                            {(getCompanySetting('company_country') || getCompanySetting('company_zipcode')) && (
                                <p>{getCompanySetting('company_country')} - {getCompanySetting('company_zipcode')}</p>
                            )}
                            {getCompanySetting('company_telephone') && <p>{t('Phone')}: {getCompanySetting('company_telephone')}</p>}
                            {getCompanySetting('company_email') && <p>{t('Email')}: {getCompanySetting('company_email')}</p>}
                        </div>
                    </div>
                    <div className="text-right w-1/2">
                        <h2 className="text-2xl font-bold mb-2">{t('POS SALE')}</h2>
                        <p className="text-lg font-semibold">{sale.sale_number}</p>
                        <div className="text-sm mt-2">
                            <p>{t('Date')}: {formatDate(sale.created_at)}</p>
                        </div>
                    </div>
                </div>

                {/* Customer Information */}
                <div className="flex justify-between mb-12">
                    <div className="w-1/2">
                        <h3 className="font-bold mb-3">{t('CUSTOMER')}</h3>
                        <div className="text-sm space-y-1">
                            <p className="font-semibold">{sale.customer?.name || t('Walk-in Customer')}</p>
                            {sale.customer?.email && <p>{sale.customer.email}</p>}
                        </div>
                    </div>
                    <div className="text-right w-1/2">
                        <h3 className="font-bold mb-3">{t('WAREHOUSE')}</h3>
                        <div className="text-sm space-y-1">
                            <p className="font-semibold">{sale.warehouse?.name || '-'}</p>
                        </div>
                    </div>
                </div>

                {/* Items Table */}
                <div className="mb-8">
                    <table className="w-full table-fixed">
                        <thead>
                            <tr className="border-b border-gray-300">
                                <th className="text-left py-3 font-bold">{t('Product')}</th>
                                <th className="text-center py-3 font-bold">{t('Qty')}</th>
                                <th className="text-right py-3 font-bold">{t('Unit Price')}</th>
                                <th className="text-center py-3 font-bold">{t('Tax')}</th>
                                <th className="text-right py-3 font-bold">{t('Tax Amount')}</th>
                                <th className="text-right py-3 font-bold">{t('Total')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sale.items?.map((item, index) => (
                                <tr key={index} className="page-break-inside-avoid">
                                    <td className="py-4">
                                        <div className="font-semibold">{item.product?.name}</div>
                                        {item.product?.sku && (
                                            <div className="text-xs text-gray-500">{t('SKU')}: {item.product.sku}</div>
                                        )}
                                    </td>
                                    <td className="text-center py-4">{item.quantity}</td>
                                    <td className="text-right py-4">{formatCurrency(item.price)}</td>
                                    <td className="text-center py-4">
                                        {item.taxes && item.taxes.length > 0 ? (
                                            <div className="text-xs">
                                                {item.taxes.map((tax, taxIndex) => (
                                                    <div key={taxIndex}>{tax.tax_name} ({tax.rate}%)</div>
                                                ))}
                                            </div>
                                        ) : '-'}
                                    </td>
                                    <td className="text-right py-4">
                                        {item.tax_amount > 0 ? (
                                            <span>{formatCurrency(item.tax_amount)}</span>
                                        ) : '-'}
                                    </td>
                                    <td className="text-right py-4 font-semibold">{formatCurrency(item.total_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Summary */}
                <div className="flex justify-end mb-8">
                    <div className="w-80">
                        <div className="border border-gray-400 p-4">
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span>{t('Subtotal')}:</span>
                                    <span>{formatCurrency(sale.subtotal || 0)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('Discount')}:</span>
                                    <span className="font-medium text-red-600">-{formatCurrency(sale.discount_amount || 0)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('Tax')}:</span>
                                    <span>{formatCurrency(sale.tax_amount || 0)}</span>
                                </div>
                                <div className="border-t border-gray-400 pt-2 mt-2">
                                    <div className="flex justify-between font-bold text-lg">
                                        <span>{t('TOTAL')}:</span>
                                        <span>{formatCurrency(sale.total_amount || 0)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="border-t border-gray-400 pt-6 text-center">
                    <p className="text-sm mt-2">{t('Thank you for your business!')}</p>
                </div>
            </div>

            <style jsx global>{`
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    font-family: Arial, sans-serif;
                }

                @page {
                    margin: 0.25in;
                    size: A4;
                }

                .sale-container {
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

                    .sale-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}