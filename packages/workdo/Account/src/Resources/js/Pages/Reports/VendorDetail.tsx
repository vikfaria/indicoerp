import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { FileText, Printer } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';

interface VendorDetailProps {
    vendorData: {
        vendor: { id: number; name: string; email: string };
        date_range: { start_date: string | null; end_date: string | null };
        invoices: any[];
        returns: any[];
        debit_notes: any[];
        payments: any[];
        summary: {
            total_invoiced: number;
            total_returns: number;
            total_debit_notes: number;
            total_payments: number;
            balance: number;
        };
    };
}

export default function VendorDetail() {
    const { t } = useTranslation();
    const { vendorData, auth} = usePage<any>().props;

    const getDefaultDates = () => {
        const today = new Date();
        const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate());
        return {
            start: threeMonthsAgo.toISOString().split('T')[0],
            end: today.toISOString().split('T')[0]
        };
    };

    const defaultDates = getDefaultDates();
    const [startDate, setStartDate] = useState(vendorData.date_range.start_date || defaultDates.start);
    const [endDate, setEndDate] = useState(vendorData.date_range.end_date || defaultDates.end);


    const handleFilter = () => {
        router.get(route('account.reports.vendor-detail', vendorData.vendor.id), {
            start_date: startDate,
            end_date: endDate
        }, { preserveState: true });
    };

    // Fetch data with default dates on initial load if no dates provided
    useState(() => {
        if (!vendorData.date_range.start_date && !vendorData.date_range.end_date) {
            router.get(route('account.reports.vendor-detail', vendorData.vendor.id), {
                start_date: defaultDates.start,
                end_date: defaultDates.end
            }, { preserveState: true, replace: true });
        }
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Accounting'), url: route('account.index') },
                { label: t('Vendors'), url: route('account.vendors.index') },
                { label: t('Vendor Detail') }
            ]}
            pageTitle={t('Vendor Detail')}
            backUrl={route('account.vendors.index')}
        >
            <Head title={t('Vendor Detail')} />

            <Card className="shadow-sm mb-4">
                <CardContent className="p-6">
                    <div className="mb-4">
                        <h2 className="text-1xl font-bold">{vendorData.vendor.name}</h2>
                        {vendorData.vendor.company_name && (
                            <p className="text-sm text-gray-600">{vendorData.vendor.company_name}</p>
                        )}
                        <p className="text-sm text-gray-600">{vendorData.vendor.email}</p>
                        {vendorData.vendor.tax_number && (
                            <p className="text-sm text-gray-600">
                                {vendorData.vendor.tax_label || t('Tax Number')}: {vendorData.vendor.tax_number}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">{t('Start Date')}</label>
                            <DatePicker value={startDate} onChange={setStartDate} placeholder={t('Select start date')} />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">{t('End Date')}</label>
                            <DatePicker value={endDate} onChange={setEndDate} placeholder={t('Select end date')} />
                        </div>
                        <div className="flex items-end gap-2">
                            <Button onClick={handleFilter} size="sm">{t('Generate')}</Button>
                             {auth.user?.permissions?.includes('print-vendor-detail-report') && (
                                <Button variant="outline" size="sm" onClick={() => window.open(route('account.reports.vendor-detail.print', vendorData.vendor.id) + `?start_date=${startDate}&end_date=${endDate}&download=pdf`, '_blank')} className="gap-2">
                                    <Printer className="h-4 w-4" />
                                    {t('Download PDF')}
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p className="text-sm text-gray-600">{t('Total Billed')}</p>
                            <p className="text-lg font-semibold">{formatCurrency(vendorData.summary.total_invoiced)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">{t('Total Returns')}</p>
                            <p className="text-lg font-semibold">{formatCurrency(vendorData.summary.total_returns)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">{t('Total Debit Notes')}</p>
                            <p className="text-lg font-semibold">{formatCurrency(vendorData.summary.total_debit_notes)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">{t('Total Payments')}</p>
                            <p className="text-lg font-semibold">{formatCurrency(vendorData.summary.total_payments)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">{t('Balance')}</p>
                            <p className="text-lg font-semibold text-blue-600">{formatCurrency(vendorData.summary.balance)}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-4">
                <Card className="shadow-sm">
                    <CardContent className="p-0">
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Purchase Invoices')}</h3>
                        </div>
                        {vendorData.invoices.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-100">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Invoice Number')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Invoice Date')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Due Date')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Status')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Subtotal')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Tax')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Amount')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Balance')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {vendorData.invoices.map((invoice: any, idx: number) => (
                                            <tr key={idx} className="border-t hover:bg-gray-50">
                                                <td className="px-4 py-3 font-medium">{invoice.invoice_number}</td>
                                                <td className="px-4 py-3">{formatDate(invoice.date)}</td>
                                                <td className="px-4 py-3">{formatDate(invoice.due_date)}</td>
                                                <td className="px-4 py-3">
                                                    <span className="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 capitalize">
                                                        {invoice.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(invoice.subtotal)}</td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(invoice.tax_amount)}</td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(invoice.total_amount)}</td>
                                                <td className="px-4 py-3 text-right font-semibold">{formatCurrency(invoice.balance_amount)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <NoRecordsFound icon={FileText} title={t('No Invoices')} description={t('No purchase invoices found')} className="h-auto py-8" />
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-0">
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Purchase Returns')}</h3>
                        </div>
                        {vendorData.returns.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-100">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Return Number')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Date')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Status')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Subtotal')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Tax')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Amount')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {vendorData.returns.map((ret: any, idx: number) => (
                                            <tr key={idx} className="border-t hover:bg-gray-50">
                                                <td className="px-4 py-3 font-medium">{ret.return_number}</td>
                                                <td className="px-4 py-3">{formatDate(ret.date)}</td>
                                                <td className="px-4 py-3">
                                                    <span className="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 capitalize">
                                                        {ret.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(ret.subtotal)}</td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(ret.tax_amount)}</td>
                                                <td className="px-4 py-3 text-right font-semibold">{formatCurrency(ret.total_amount)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <NoRecordsFound icon={FileText} title={t('No Returns')} description={t('No purchase returns found')} className="h-auto py-8" />
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-0">
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Debit Notes')}</h3>
                        </div>
                        {vendorData.debit_notes.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-100">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Debit Note Number')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Date')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Status')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Amount')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Applied')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Balance')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {vendorData.debit_notes.map((note: any, idx: number) => (
                                            <tr key={idx} className="border-t hover:bg-gray-50">
                                                <td className="px-4 py-3 font-medium">{note.debit_note_number}</td>
                                                <td className="px-4 py-3">{formatDate(note.date)}</td>
                                                <td className="px-4 py-3">
                                                    <span className="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 capitalize">
                                                        {note.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(note.total_amount)}</td>
                                                <td className="px-4 py-3 text-right">{formatCurrency(note.applied_amount)}</td>
                                                <td className="px-4 py-3 text-right font-semibold">{formatCurrency(note.balance_amount)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <NoRecordsFound icon={FileText} title={t('No Debit Notes')} description={t('No debit notes found')} className="h-auto py-8" />
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-0">
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Vendor Payments')}</h3>
                        </div>
                        {vendorData.payments.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-100">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Payment Number')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Date')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Bank Account')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Reference')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Status')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Amount')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {vendorData.payments.map((payment: any, idx: number) => (
                                            <tr key={idx} className="border-t hover:bg-gray-50">
                                                <td className="px-4 py-3 font-medium">{payment.payment_number}</td>
                                                <td className="px-4 py-3">{formatDate(payment.date)}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{payment.bank_account || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{payment.reference_number || '-'}</td>
                                                <td className="px-4 py-3">
                                                    <span className="px-2 py-1 text-xs rounded-full bg-teal-100 text-teal-800 capitalize">
                                                        {payment.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold">{formatCurrency(payment.amount)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <NoRecordsFound icon={FileText} title={t('No Payments')} description={t('No vendor payments found')} className="h-auto py-8" />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
