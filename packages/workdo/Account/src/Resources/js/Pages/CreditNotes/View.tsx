import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { formatCurrency, formatDate, resolveDocumentIssuer, resolveSalesDocumentCounterparty } from '@/utils/helpers';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Download } from 'lucide-react';

interface CreditNote {
    id: number;
    credit_note_number: string;
    credit_note_date: string;
    customer: {
        name: string;
        email: string;
    };
    invoice?: {
        invoice_number?: string;
    };
    total_amount: number;
    applied_amount: number;
    balance_amount: number;
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    status: string;
    reason: string;
    notes?: string;
    issuer_snapshot?: Record<string, any> | null;
    counterparty_snapshot?: Record<string, any> | null;
    items: Array<{
        id: number;
        product: {
            name: string;
            sku?: string;
            description?: string;
        };
        quantity: number;
        unit_price: number;
        discount_percentage: number;
        discount_amount: number;
        tax_percentage: number;
        tax_amount: number;
        total_amount: number;
        taxes?: Array<{
            tax_name: string;
            tax_rate: number;
        }>;
    }>;
    sales_return?: {
        return_number: string;
    };
    applications: Array<{
        id: number;
        applied_amount: number;
        application_date: string;
        payment: {
            payment_number: string;
        };
    }>;
}

interface ViewProps {
    creditNote: CreditNote;
    auth: any;
    [key: string]: any;
}



function View() {
    const { t } = useTranslation();
    const { creditNote, auth } = usePage<ViewProps>().props;
    const issuer = resolveDocumentIssuer(creditNote as Record<string, any>);
    const counterparty = resolveSalesDocumentCounterparty(creditNote as Record<string, any>);
    const counterpartyTaxLabel = counterparty.tax_label || issuer.tax_label || t('Tax Number');
    const issuerTaxLabel = issuer.tax_label || t('Tax Number');

    const downloadPDF = () => {
        const printUrl = route('account.credit-notes.print', creditNote.id) + '?download=pdf';
        window.open(printUrl, '_blank');
    };


    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Credit Notes'), url: route('account.credit-notes.index')},
                {label: t('Credit Note Details')}
            ]}
            pageTitle={`${t('Credit Note')} #${creditNote.credit_note_number}`}
            backUrl={route('account.credit-notes.index')}
        >
            <Head title={`${t('Credit Note')} #${creditNote.credit_note_number}`} />

            <div className="space-y-6">
                {/* Credit Note Header */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex justify-between items-center mb-6">
                            <div>
                                <p className="text-lg text-muted-foreground">#{creditNote.credit_note_number}</p>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className={`px-2 py-1 rounded-full text-sm ${
                                    creditNote.status === 'approved' ? 'bg-green-100 text-green-800' :
                                    creditNote.status === 'partial' ? 'bg-yellow-100 text-yellow-800' :
                                    creditNote.status === 'applied' ? 'bg-blue-100 text-blue-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {t(creditNote.status.charAt(0).toUpperCase() + creditNote.status.slice(1))}
                                </span>
                                <div className="text-right">
                                    <div className="text-2xl font-bold">{formatCurrency(parseFloat(creditNote.total_amount.toString()))}</div>
                                    <div className="text-sm text-muted-foreground">{t('Total Amount')}</div>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 className="font-semibold mb-2">{t('CUSTOMER')}</h3>
                                <div className="text-sm space-y-1">
                                    <div className="font-medium">{counterparty.company_name || creditNote.customer?.name}</div>
                                    {counterparty.company_name && (
                                        <div>{creditNote.customer?.name}</div>
                                    )}
                                    <div className="text-muted-foreground">{counterparty.email || creditNote.customer?.email}</div>
                                    {counterparty.tax_number && (
                                        <div className="text-muted-foreground">
                                            {counterpartyTaxLabel}: {counterparty.tax_number}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div>
                                <h3 className="font-semibold mb-2">{t('DETAILS')}</h3>
                                <div className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">{t('Date')}</span>
                                        <span>{formatDate(creditNote.credit_note_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">{t('Reason')}</span>
                                        <span>{creditNote.reason}</span>
                                    </div>
                                    {creditNote.invoice?.invoice_number && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">{t('Invoice')}</span>
                                            <span>{creditNote.invoice.invoice_number}</span>
                                        </div>
                                    )}
                                    {creditNote.sales_return && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">{t('Sales Return')}</span>
                                            <span>{creditNote.sales_return.return_number}</span>
                                        </div>
                                    )}
                                    {issuer.tax_number && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">{issuerTaxLabel}</span>
                                            <span>{issuer.tax_number}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="mt-4 p-3 bg-blue-50 rounded">
                                    <div className="flex justify-between items-center">
                                        <div className="flex gap-2">
                                            {auth.user?.permissions?.includes('view-credit-notes') && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={downloadPDF}
                                                >
                                                    <Download className="h-4 w-4 mr-2" />
                                                    {t('Download PDF')}
                                                </Button>
                                            )}
                                            {creditNote.status === 'draft' && auth.user?.permissions?.includes('approve-credit-notes') && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => router.post(route('account.credit-notes.approve', creditNote.id), {}, {
                                                        onSuccess: () => {
                                                            router.reload();
                                                        }
                                                    })}
                                                >
                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                    {t('Approve Credit Note')}
                                                </Button>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <div className="text-xl font-bold text-green-600">{formatCurrency(parseFloat(creditNote.balance_amount.toString()))}</div>
                                            <div className="text-sm text-muted-foreground">{t('Balance Amount')}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {creditNote.notes && (
                            <div className="mt-4 pt-4 border-t">
                                <span className="font-medium text-sm">{t('Notes')}:</span>
                                <span className="text-sm text-muted-foreground ml-2">{creditNote.notes}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Credit Note Items */}
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-semibold">
                            {t('Credit Note Items')}
                        </h3>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Product')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Qty')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Unit Price')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Discount')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Tax')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {creditNote.items?.map((item, index) => (
                                        <tr key={index}>
                                            <td className="px-4 py-4">
                                                <div className="font-medium">{item.product?.name}</div>
                                                {item.product?.sku && (
                                                    <div className="text-sm text-muted-foreground">SKU: {item.product.sku}</div>
                                                )}
                                                {item.product?.description && (
                                                    <div className="text-sm text-muted-foreground mt-1">{item.product.description}</div>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-right">{item.quantity}</td>
                                            <td className="px-4 py-4 text-right">{formatCurrency(parseFloat(item.unit_price.toString()))}</td>
                                            <td className="px-4 py-4 text-right">
                                                {item.discount_percentage > 0 ? (
                                                    <div>
                                                        <div>{item.discount_percentage}%</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            -{formatCurrency(parseFloat(item.discount_amount.toString()))}
                                                        </div>
                                                    </div>
                                                ) : '-'}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                {item.taxes && item.taxes.length > 0 ? (
                                                    <div>
                                                        {item.taxes.map((tax, taxIndex) => (
                                                            <div key={taxIndex} className="text-sm">{tax.tax_name} ({tax.tax_rate}%)</div>
                                                        ))}
                                                        <div className="text-sm text-muted-foreground">
                                                            {formatCurrency(parseFloat(item.tax_amount.toString()))}
                                                        </div>
                                                    </div>
                                                ) : item.tax_percentage > 0 ? (
                                                    <div>
                                                        <div>{item.tax_percentage}%</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {formatCurrency(parseFloat(item.tax_amount.toString()))}
                                                        </div>
                                                    </div>
                                                ) : '-'}
                                            </td>
                                            <td className="px-4 py-4 text-right font-semibold">
                                                {formatCurrency(parseFloat(item.total_amount.toString()))}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Credit Note Summary */}
                        <div className="mt-6 flex justify-end">
                            <div className="w-80 space-y-3">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">{t('Subtotal')}</span>
                                    <span className="font-medium">{formatCurrency(parseFloat(creditNote.subtotal.toString()))}</span>
                                </div>
                                {creditNote.discount_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{t('Discount')}</span>
                                        <span className="font-medium text-red-600">-{formatCurrency(parseFloat(creditNote.discount_amount.toString()))}</span>
                                    </div>
                                )}
                                {creditNote.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{t('Tax')}</span>
                                        <span className="font-medium">{formatCurrency(parseFloat(creditNote.tax_amount.toString()))}</span>
                                    </div>
                                )}
                                <div className="border-t pt-3">
                                    <div className="flex justify-between">
                                        <span className="font-semibold">{t('Total Credit Amount')}</span>
                                        <span className="font-bold text-lg">{formatCurrency(parseFloat(creditNote.total_amount.toString()))}</span>
                                    </div>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">{t('Applied Amount')}</span>
                                    <span className="font-medium">{formatCurrency(parseFloat(creditNote.applied_amount.toString()))}</span>
                                </div>
                                <div className="border-t pt-3">
                                    <div className="flex justify-between">
                                        <span className="font-semibold text-green-600">{t('Balance Amount')}</span>
                                        <span className="font-bold text-lg text-green-600">{formatCurrency(parseFloat(creditNote.balance_amount.toString()))}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Applications */}
                {creditNote.applications.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Applications')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="min-w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Payment')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Applied Amount')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {creditNote.applications.map((application) => (
                                            <tr key={application.id}>
                                                <td className="px-4 py-4 text-sm text-gray-900">{application.payment.payment_number}</td>
                                                <td className="px-4 py-4 text-right text-sm text-gray-500">{formatCurrency(parseFloat(application.applied_amount.toString()))}</td>
                                                <td className="px-4 py-4 text-right text-sm text-gray-500">{formatDate(application.application_date)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

export default View;
