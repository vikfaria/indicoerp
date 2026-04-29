import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Download, FileText } from 'lucide-react';
import { formatCurrency, formatDate } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface MozambiqueFiscalMapProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    };
}

export default function MozambiqueFiscalMap({ financialYear }: MozambiqueFiscalMapProps) {
    const { t } = useTranslation();
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-fiscal-map'), {
                params: { from_date: fromDate, to_date: toDate },
            });
            setData(response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            className="gap-2"
                            onClick={() => window.open(route('account.reports.mozambique-fiscal-map.export') + `?from_date=${fromDate}&to_date=${toDate}`, '_blank')}
                        >
                            <Download className="h-4 w-4" />
                            {t('Export CSV')}
                        </Button>
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Mozambique Fiscal Map')}</h3>
                            <p className="text-sm text-gray-600">
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}
                            </p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <tbody>
                                    <tr className="bg-green-50">
                                        <td className="px-4 py-3 font-semibold">{t('Sales VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.sales.tax_amount)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Sales Documents')}</td>
                                        <td className="px-4 py-3 text-right">{data.sales.documents}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Sales Taxable Base')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.sales.taxable_base)}</td>
                                    </tr>

                                    <tr className="bg-red-50 border-t">
                                        <td className="px-4 py-3 font-semibold">{t('Purchase VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.purchases.tax_amount)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Purchase Documents')}</td>
                                        <td className="px-4 py-3 text-right">{data.purchases.documents}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Purchase Taxable Base')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.purchases.taxable_base)}</td>
                                    </tr>

                                    <tr className="border-t bg-gray-100">
                                        <td className="px-4 py-3 font-semibold">{t('Credit Notes VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.credit_notes.tax_amount)}</td>
                                    </tr>
                                    <tr className="border-t bg-gray-100">
                                        <td className="px-4 py-3 font-semibold">{t('Debit Notes VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.debit_notes.tax_amount)}</td>
                                    </tr>

                                    <tr className="border-t-2 bg-blue-50 font-semibold">
                                        <td className="px-4 py-3">{t('Output VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.vat.output_vat)}</td>
                                    </tr>
                                    <tr className="border-t bg-blue-50 font-semibold">
                                        <td className="px-4 py-3">{t('Input VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.vat.input_vat)}</td>
                                    </tr>
                                    <tr className="border-t bg-blue-100 font-bold">
                                        <td className="px-4 py-3">{t('Net VAT Payable')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.vat.net_vat_payable)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Mozambique Fiscal Map')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}

