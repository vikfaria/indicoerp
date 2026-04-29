import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Quotation } from './types';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { getStatusBadgeClasses } from './utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { FileText, Download, Send, RefreshCw } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface ViewProps {
    quotation: Quotation;
    auth: any;
    [key: string]: any;
}

export default function View() {
    const { t } = useTranslation();
    const { quotation, auth } = usePage<ViewProps>().props;


    const downloadPDF = () => {
        const printUrl = route('quotations.print', quotation.id) + '?download=pdf';
        window.open(printUrl, '_blank');
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Quotations'), url: route('quotations.index')},
                {label: t('Quotation Details')}
            ]}
            pageTitle={`${t('Quotation')} #${quotation.quotation_number}`}
            backUrl={route('quotations.index')}
        >
            <Head title={`${t('Quotation')} #${quotation.quotation_number}`} />

            <div className="space-y-6">
                <Card>
                    <CardContent className="p-6">
                        <div className="flex justify-between items-center mb-6">
                            <div>
                                <div className="flex items-center gap-2">
                                    <p className="text-lg text-muted-foreground">#{quotation.quotation_number}</p>
                                    {quotation.revision_number > 1 && (
                                        <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                            v{quotation.revision_number}
                                        </span>
                                    )}
                                </div>
                                {quotation.parent_quotation_id && (
                                    <p className="text-sm mt-1">
                                        {t('Revision of')} #{quotation.parent_quotation?.quotation_number}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-4">
                                <span className={getStatusBadgeClasses(quotation.status)}>
                                    {t(quotation.status.toUpperCase())}
                                </span>
                                <div className="text-right">
                                    <div className="text-2xl font-bold">{formatCurrency(quotation.total_amount)}</div>
                                    <div className="text-sm text-muted-foreground">{t('Total Amount')}</div>
                                </div>
                            </div>
                        </div>

                        <div className={`grid grid-cols-1 gap-6 ${quotation.customer_details?.billing_address || quotation.customer_details?.shipping_address ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                            <div>
                                <h3 className="font-semibold mb-2">{t('CUSTOMER')}</h3>
                                <div className="text-sm space-y-1">
                                    <div className="font-medium">{quotation.customer?.name}</div>
                                    <div className="text-muted-foreground">{quotation.customer?.email}</div>
                                </div>
                                {quotation.customer_details?.billing_address && (
                                    <div className="mt-3">
                                        <div className="font-medium text-sm mb-1">{t('Billing Address')}</div>
                                        <div className="text-sm text-muted-foreground space-y-1">
                                            <div>{quotation.customer_details.billing_address.name}</div>
                                            <div>{quotation.customer_details.billing_address.address_line_1}</div>
                                            <div>{quotation.customer_details.billing_address.city}, {quotation.customer_details.billing_address.state} {quotation.customer_details.billing_address.zip_code}</div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {quotation.customer_details?.shipping_address && (
                                <div>
                                    <h3 className="font-semibold mb-2">{t('SHIPPING ADDRESS')}</h3>
                                    <div className="text-sm text-muted-foreground space-y-1">
                                        <div>{quotation.customer_details.shipping_address.name}</div>
                                        <div>{quotation.customer_details.shipping_address.address_line_1}</div>
                                        <div>{quotation.customer_details.shipping_address.city}, {quotation.customer_details.shipping_address.state} {quotation.customer_details.shipping_address.zip_code}</div>
                                    </div>
                                </div>
                            )}

                            <div>
                                <h3 className="font-semibold mb-2">{t('DETAILS')}</h3>
                                <div className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">{t('Quotation Date')}</span>
                                        <span>{formatDate(quotation.quotation_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">{t('Due Date')}</span>
                                        <span className={new Date(quotation.due_date) < new Date() ? 'text-red-600' : ''}>
                                            {formatDate(quotation.due_date)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">{t('Warehouse')}</span>
                                        <span>{quotation.warehouse?.name || '-'}</span>
                                    </div>
                                    {quotation.payment_terms && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">{t('Terms')}</span>
                                            <span>{quotation.payment_terms}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="mt-4 p-3 bg-blue-50 rounded">
                                    <div className="flex justify-between items-center">
                                        <div className="flex flex-wrap gap-2">
                                            {auth.user?.permissions?.includes('print-quotations') && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={downloadPDF}
                                                >
                                                    <Download className="h-4 w-4 mr-2" />
                                                    {t('Download PDF')}
                                                </Button>
                                            )}
                                            {!quotation.converted_to_invoice && auth.user?.permissions?.includes('convert-to-invoice-quotations') && quotation.status === 'accepted' && (
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                size="sm"
                                                                onClick={() => router.post(route('quotations.convert-to-invoice', quotation.id), {}, {
                                                                    onSuccess: () => {
                                                                        router.reload();
                                                                    }
                                                                })}
                                                            >
                                                                <RefreshCw className="h-4 w-4 mr-2" />
                                                                {t('Convert to Invoice')}
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{t('Convert to Invoice')}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            )}

                                        </div>
                                        <div className="text-right">
                                            <div className="text-xl font-bold text-blue-600">{formatCurrency(quotation.total_amount)}</div>
                                            <div className="text-sm text-muted-foreground">{t('Quotation Amount')}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {quotation.notes && (
                            <div className="mt-4 pt-4 border-t">
                                <span className="font-medium text-sm">{t('Notes')}:</span>
                                <span className="text-sm text-muted-foreground ml-2">{quotation.notes}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-semibold">
                            {t('Quotation Items')}
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
                                    {quotation.items?.map((item, index) => (
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
                                            <td className="px-4 py-4 text-right">{formatCurrency(item.unit_price)}</td>
                                            <td className="px-4 py-4 text-right">
                                                {item.discount_percentage > 0 ? (
                                                    <div>
                                                        <div>{item.discount_percentage}%</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            -{formatCurrency(item.discount_amount)}
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
                                                            {formatCurrency(item.tax_amount)}
                                                        </div>
                                                    </div>
                                                ) : item.tax_percentage > 0 ? (
                                                    <div>
                                                        <div>{item.tax_percentage}%</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {formatCurrency(item.tax_amount)}
                                                        </div>
                                                    </div>
                                                ) : '-'}
                                            </td>
                                            <td className="px-4 py-4 text-right font-semibold">
                                                {formatCurrency(item.total_amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <div className="w-80 space-y-3">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">{t('Subtotal')}</span>
                                    <span className="font-medium">{formatCurrency(quotation.subtotal)}</span>
                                </div>
                                {quotation.discount_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{t('Discount')}</span>
                                        <span className="font-medium text-red-600">-{formatCurrency(quotation.discount_amount)}</span>
                                    </div>
                                )}
                                {quotation.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{t('Tax')}</span>
                                        <span className="font-medium">{formatCurrency(quotation.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="border-t pt-3">
                                    <div className="flex justify-between">
                                        <span className="font-semibold">{t('Total Amount')}</span>
                                        <span className="font-bold text-lg">{formatCurrency(quotation.total_amount)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>


        </AuthenticatedLayout>
    );
}