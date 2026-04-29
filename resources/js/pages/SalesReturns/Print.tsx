import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanyTaxLabel, resolveDocumentIssuer, resolveSalesDocumentCounterparty } from '@/utils/helpers';
import { SalesReturn } from './types';

interface PrintProps {
    return: SalesReturn;
    [key: string]: any;
}

const formatReason = (reason?: string): string => {
    if (!reason) {
        return '-';
    }

    return reason
        .split('_')
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');
};

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { return: salesReturn } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const issuer = resolveDocumentIssuer(salesReturn as Record<string, any>, page.props);
    const customer = resolveSalesDocumentCounterparty(salesReturn as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const companyTaxNumber = issuer.tax_number || null;
    const counterpartyTaxLabel = customer.tax_label || companyTaxLabel;
    const documentTitle = salesReturn.status === 'draft' ? t('SALES RETURN') : t('CREDIT NOTE');

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.return-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `sales-return-${salesReturn.return_number}.pdf`,
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
            <Head title={documentTitle} />

            {isDownloading && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div className="rounded-lg bg-white p-6 shadow-lg">
                        <div className="flex items-center space-x-3">
                            <div className="h-6 w-6 animate-spin rounded-full border-b-2 border-blue-600"></div>
                            <p className="text-lg font-semibold text-gray-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="return-container mx-auto max-w-4xl bg-white p-8">
                <div className="mb-8 flex items-start justify-between">
                    <div className="w-1/2">
                        <h1 className="mb-4 text-2xl font-bold">{issuer.company_name || 'YOUR COMPANY'}</h1>
                        <div className="space-y-1 text-sm">
                            {issuer.company_address && <p>{issuer.company_address}</p>}
                            {(issuer.company_city || issuer.company_state || issuer.company_zipcode) && (
                                <p>
                                    {issuer.company_city}{issuer.company_state && `, ${issuer.company_state}`} {issuer.company_zipcode}
                                </p>
                            )}
                            {issuer.company_country && <p>{issuer.company_country}</p>}
                            {issuer.company_telephone && <p>{t('Phone')}: {issuer.company_telephone}</p>}
                            {issuer.company_email && <p>{t('Email')}: {issuer.company_email}</p>}
                            {issuer.registration_number && <p>{t('Registration')}: {issuer.registration_number}</p>}
                            {companyTaxNumber && <p>{companyTaxLabel}: {companyTaxNumber}</p>}
                        </div>
                    </div>
                    <div className="w-1/2 text-right">
                        <h2 className="mb-2 text-2xl font-bold">{documentTitle}</h2>
                        <p className="text-lg font-semibold">#{salesReturn.return_number}</p>
                        <div className="mt-2 space-y-1 text-sm">
                            <p>{t('Date')}: {formatDate(salesReturn.return_date)}</p>
                            <p>{t('Status')}: {t(salesReturn.status.toUpperCase())}</p>
                            <p>{t('Reference Invoice')}: #{salesReturn.original_invoice?.invoice_number || '-'}</p>
                        </div>
                    </div>
                </div>

                <div className="mb-8 flex justify-between">
                    <div className="w-1/2">
                        <h3 className="mb-3 font-bold">{t('CUSTOMER')}</h3>
                        <div className="space-y-1 text-sm">
                            <p className="font-semibold">{customer.name}</p>
                            {customer.company_name && <p>{customer.company_name}</p>}
                            <p>{customer.email}</p>
                            {customer.tax_number && <p>{counterpartyTaxLabel}: {customer.tax_number}</p>}
                            {customer.billing_address && (
                                <>
                                    <p>{customer.billing_address.name}</p>
                                    <p>{customer.billing_address.address_line_1}</p>
                                    <p>{customer.billing_address.city}, {customer.billing_address.state} {customer.billing_address.zip_code}</p>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="w-1/2 text-right">
                        <h3 className="mb-3 font-bold">{t('DETAILS')}</h3>
                        <div className="space-y-1 text-sm">
                            <p>{t('Warehouse')}: {salesReturn.warehouse?.name || '-'}</p>
                            <p>{t('Reason')}: {t(formatReason(salesReturn.reason))}</p>
                        </div>
                    </div>
                </div>

                <div className="mb-8">
                    <table className="w-full table-fixed">
                        <thead>
                            <tr className="border-b border-gray-300">
                                <th className="py-3 text-left font-bold">{t('ITEM')}</th>
                                <th className="py-3 text-center font-bold">{t('QTY')}</th>
                                <th className="py-3 text-right font-bold">{t('PRICE')}</th>
                                <th className="py-3 text-right font-bold">{t('DISCOUNT')}</th>
                                <th className="py-3 text-right font-bold">{t('TAX')}</th>
                                <th className="py-3 text-right font-bold">{t('TOTAL')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {salesReturn.items?.map((item, index) => (
                                <tr key={index} className="page-break-inside-avoid">
                                    <td className="py-4">
                                        <div className="font-semibold">{item.product?.name}</div>
                                        {item.product?.sku && <div className="text-xs text-gray-500">{t('SKU')}: {item.product.sku}</div>}
                                    </td>
                                    <td className="py-4 text-center">{item.return_quantity || item.quantity}</td>
                                    <td className="py-4 text-right">{formatCurrency(item.unit_price)}</td>
                                    <td className="py-4 text-right">
                                        {item.discount_percentage > 0 ? (
                                            <>
                                                <div className="text-sm">{item.discount_percentage}%</div>
                                                <div className="text-sm font-medium">-{formatCurrency(item.discount_amount)}</div>
                                            </>
                                        ) : (
                                            <div className="text-sm">0%</div>
                                        )}
                                    </td>
                                    <td className="py-4 text-right">
                                        {item.taxes && item.taxes.length > 0 ? (
                                            <>
                                                {item.taxes.map((tax, taxIndex) => (
                                                    <div key={taxIndex} className="text-sm">{tax.tax_name} ({tax.tax_rate}%)</div>
                                                ))}
                                                <div className="text-sm font-medium">{formatCurrency(item.tax_amount)}</div>
                                            </>
                                        ) : item.tax_percentage > 0 ? (
                                            <>
                                                <div className="text-sm">{item.tax_percentage}%</div>
                                                <div className="text-sm font-medium">{formatCurrency(item.tax_amount)}</div>
                                            </>
                                        ) : (
                                            <div className="text-sm">0%</div>
                                        )}
                                    </td>
                                    <td className="py-4 text-right font-semibold">{formatCurrency(item.total_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mb-4 flex justify-end">
                    <div className="w-80">
                        <div className="border border-gray-400 p-4">
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span>{t('Subtotal')}:</span>
                                    <span>{formatCurrency(salesReturn.subtotal)}</span>
                                </div>
                                {salesReturn.discount_amount > 0 && (
                                    <div className="flex justify-between">
                                        <span>{t('Discount')}:</span>
                                        <span>-{formatCurrency(salesReturn.discount_amount)}</span>
                                    </div>
                                )}
                                {salesReturn.tax_amount > 0 && (
                                    <div className="flex justify-between">
                                        <span>{t('Tax')}:</span>
                                        <span>{formatCurrency(salesReturn.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="mt-2 border-t border-gray-400 pt-2">
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>{t('TOTAL')}:</span>
                                        <span>{formatCurrency(salesReturn.total_amount)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {(salesReturn.notes || salesReturn.reason) && (
                    <div className="mt-8 border-t pt-6 text-sm">
                        <p><span className="font-semibold">{t('Reason')}:</span> {t(formatReason(salesReturn.reason))}</p>
                        {salesReturn.notes && <p className="mt-2"><span className="font-semibold">{t('Notes')}:</span> {salesReturn.notes}</p>}
                    </div>
                )}
            </div>
        </div>
    );
}
