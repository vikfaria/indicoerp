import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, resolveDocumentIssuer, resolvePurchaseDocumentCounterparty } from '@/utils/helpers';

interface DebitNote {
    id: number;
    debit_note_number: string;
    debit_note_date: string;
    vendor?: {
        name?: string;
        email?: string;
    };
    invoice?: {
        invoice_number?: string;
    };
    total_amount: number | string;
    applied_amount: number | string;
    balance_amount: number | string;
    subtotal: number | string;
    tax_amount: number | string;
    discount_amount: number | string;
    status: string;
    reason: string;
    notes?: string;
    issuer_snapshot?: Record<string, any> | null;
    counterparty_snapshot?: Record<string, any> | null;
    items: Array<{
        id: number;
        product?: {
            name?: string;
            sku?: string;
            description?: string;
        };
        quantity: number | string;
        unit_price: number | string;
        discount_percentage: number | string;
        discount_amount: number | string;
        tax_percentage: number | string;
        tax_amount: number | string;
        total_amount: number | string;
        taxes?: Array<{
            tax_name: string;
            tax_rate: number | string;
        }>;
    }>;
    purchase_return?: {
        return_number: string;
    };
    applications: Array<{
        id: number;
        applied_amount: number | string;
        application_date: string;
        payment?: {
            payment_number?: string;
        };
    }>;
}

interface PrintProps {
    debitNote: DebitNote;
    [key: string]: any;
}

export default function Print() {
    const { t } = useTranslation();
    const { debitNote } = usePage<PrintProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    const issuer = resolveDocumentIssuer(debitNote as Record<string, any>);
    const counterparty = resolvePurchaseDocumentCounterparty(debitNote as Record<string, any>);
    const counterpartyTaxLabel = counterparty.tax_label || issuer.tax_label || t('Tax Number');
    const issuerTaxLabel = issuer.tax_label || t('Tax Number');

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.debit-note-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `debit-note-${debitNote.debit_note_number}.pdf`,
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
            <Head title={`${t('Debit Note')} #${debitNote.debit_note_number}`} />

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

            <div className="debit-note-container bg-white max-w-4xl mx-auto p-12">
                <div className="flex justify-between items-start mb-10">
                    <div className="w-1/2">
                        <h1 className="text-2xl font-bold mb-3">{issuer.company_name || 'YOUR COMPANY'}</h1>
                        <div className="text-sm space-y-1">
                            {issuer.company_address && <p>{issuer.company_address}</p>}
                            {(issuer.company_city || issuer.company_state || issuer.company_zipcode) && (
                                <p>
                                    {issuer.company_city}{issuer.company_state ? `, ${issuer.company_state}` : ''} {issuer.company_zipcode}
                                </p>
                            )}
                            {issuer.company_country && <p>{issuer.company_country}</p>}
                            {issuer.company_telephone && <p>{t('Phone')}: {issuer.company_telephone}</p>}
                            {issuer.company_email && <p>{t('Email')}: {issuer.company_email}</p>}
                            {issuer.registration_number && <p>{t('Registration')}: {issuer.registration_number}</p>}
                            {issuer.tax_number && <p>{issuerTaxLabel}: {issuer.tax_number}</p>}
                        </div>
                    </div>
                    <div className="text-right w-1/2">
                        <h2 className="text-2xl font-bold mb-2">{t('DEBIT NOTE')}</h2>
                        <p className="text-lg font-semibold">#{debitNote.debit_note_number}</p>
                        <div className="text-sm mt-2 space-y-1">
                            <p>{t('Date')}: {formatDate(debitNote.debit_note_date)}</p>
                            <p>{t('Status')}: {t(debitNote.status.charAt(0).toUpperCase() + debitNote.status.slice(1))}</p>
                            <p>{t('Reason')}: {debitNote.reason}</p>
                            {debitNote.invoice?.invoice_number && <p>{t('Invoice')}: {debitNote.invoice.invoice_number}</p>}
                            {debitNote.purchase_return?.return_number && <p>{t('Purchase Return')}: {debitNote.purchase_return.return_number}</p>}
                        </div>
                    </div>
                </div>

                <div className="mb-8">
                    <h3 className="font-bold mb-3">{t('VENDOR')}</h3>
                    <div className="text-sm space-y-1">
                        <p className="font-semibold">{counterparty.company_name || debitNote.vendor?.name}</p>
                        {counterparty.company_name && debitNote.vendor?.name && <p>{debitNote.vendor.name}</p>}
                        <p>{counterparty.email || debitNote.vendor?.email || '-'}</p>
                        {counterparty.tax_number && (
                            <p>{counterpartyTaxLabel}: {counterparty.tax_number}</p>
                        )}
                    </div>
                </div>

                <div className="mb-8">
                    <table className="w-full table-fixed">
                        <thead>
                            <tr className="border-b border-gray-300">
                                <th className="text-left py-3 font-bold">{t('ITEM')}</th>
                                <th className="text-right py-3 font-bold">{t('QTY')}</th>
                                <th className="text-right py-3 font-bold">{t('PRICE')}</th>
                                <th className="text-right py-3 font-bold">{t('DISCOUNT')}</th>
                                <th className="text-right py-3 font-bold">{t('TAX')}</th>
                                <th className="text-right py-3 font-bold">{t('TOTAL')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {debitNote.items?.map((item, index) => (
                                <tr key={index} className="page-break-inside-avoid">
                                    <td className="py-4">
                                        <div className="font-semibold">{item.product?.name || '-'}</div>
                                        {item.product?.sku && (
                                            <div className="text-xs text-gray-500">{t('SKU')}: {item.product.sku}</div>
                                        )}
                                        {item.product?.description && (
                                            <div className="text-xs text-gray-500">{item.product.description}</div>
                                        )}
                                    </td>
                                    <td className="text-right py-4">{item.quantity}</td>
                                    <td className="text-right py-4">{formatCurrency(parseFloat(item.unit_price.toString()))}</td>
                                    <td className="text-right py-4">
                                        {parseFloat(item.discount_percentage.toString()) > 0 ? (
                                            <>
                                                <div className="text-sm">{item.discount_percentage}%</div>
                                                <div className="text-sm font-medium">-{formatCurrency(parseFloat(item.discount_amount.toString()))}</div>
                                            </>
                                        ) : (
                                            <div className="text-sm">0%</div>
                                        )}
                                    </td>
                                    <td className="text-right py-4">
                                        {item.taxes && item.taxes.length > 0 ? (
                                            <>
                                                {item.taxes.map((tax, taxIndex) => (
                                                    <div key={taxIndex} className="text-sm">{tax.tax_name} ({tax.tax_rate}%)</div>
                                                ))}
                                                <div className="text-sm font-medium">{formatCurrency(parseFloat(item.tax_amount.toString()))}</div>
                                            </>
                                        ) : parseFloat(item.tax_percentage.toString()) > 0 ? (
                                            <>
                                                <div className="text-sm">{item.tax_percentage}%</div>
                                                <div className="text-sm font-medium">{formatCurrency(parseFloat(item.tax_amount.toString()))}</div>
                                            </>
                                        ) : (
                                            <div className="text-sm">0%</div>
                                        )}
                                    </td>
                                    <td className="text-right py-4 font-semibold">{formatCurrency(parseFloat(item.total_amount.toString()))}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end mb-8">
                    <div className="w-80">
                        <div className="border border-gray-400 p-4">
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span>{t('Subtotal')}:</span>
                                    <span>{formatCurrency(parseFloat(debitNote.subtotal.toString()))}</span>
                                </div>
                                {parseFloat(debitNote.discount_amount.toString()) > 0 && (
                                    <div className="flex justify-between">
                                        <span>{t('Discount')}:</span>
                                        <span>-{formatCurrency(parseFloat(debitNote.discount_amount.toString()))}</span>
                                    </div>
                                )}
                                {parseFloat(debitNote.tax_amount.toString()) > 0 && (
                                    <div className="flex justify-between">
                                        <span>{t('Tax')}:</span>
                                        <span>{formatCurrency(parseFloat(debitNote.tax_amount.toString()))}</span>
                                    </div>
                                )}
                                <div className="border-t border-gray-400 pt-2 mt-2">
                                    <div className="flex justify-between font-bold text-lg">
                                        <span>{t('TOTAL')}:</span>
                                        <span>{formatCurrency(parseFloat(debitNote.total_amount.toString()))}</span>
                                    </div>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('Applied Amount')}:</span>
                                    <span>{formatCurrency(parseFloat(debitNote.applied_amount.toString()))}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('Balance Amount')}:</span>
                                    <span className="font-semibold">{formatCurrency(parseFloat(debitNote.balance_amount.toString()))}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {debitNote.applications.length > 0 && (
                    <div className="mb-8">
                        <h3 className="font-bold mb-3">{t('Applications')}</h3>
                        <table className="w-full border-collapse">
                            <thead>
                                <tr className="border-b border-gray-300">
                                    <th className="text-left py-2 font-semibold">{t('Payment')}</th>
                                    <th className="text-right py-2 font-semibold">{t('Applied Amount')}</th>
                                    <th className="text-right py-2 font-semibold">{t('Date')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {debitNote.applications.map((application) => (
                                    <tr key={application.id} className="border-b border-gray-200">
                                        <td className="py-2">{application.payment?.payment_number || '-'}</td>
                                        <td className="py-2 text-right">{formatCurrency(parseFloat(application.applied_amount.toString()))}</td>
                                        <td className="py-2 text-right">{formatDate(application.application_date)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {debitNote.notes && (
                    <div className="border-t border-gray-400 pt-4 text-sm">
                        <span className="font-semibold">{t('Notes')}: </span>
                        <span>{debitNote.notes}</span>
                    </div>
                )}

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

                .debit-note-container {
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

                    .debit-note-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}
