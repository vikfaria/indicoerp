import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Printer, FileText, ChevronDown, ChevronRight } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';

import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface JournalEntryItem {
    account_code: string;
    account_name: string;
    description: string;
    debit: number;
    credit: number;
}

interface JournalEntryData {
    id: number;
    journal_number: string;
    date: string;
    reference_type: string;
    description: string;
    total_debit: number;
    total_credit: number;
    status: string;
    is_balanced: boolean;
    items: JournalEntryItem[];
}

interface JournalEntryProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    }
}

export default function JournalEntry({ financialYear }: JournalEntryProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [status, setStatus] = useState('');
    const [data, setData] = useState<JournalEntryData[]>([]);
    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('double-entry.reports.journal-entry'), {
                params: { from_date: fromDate, to_date: toDate, status }
            });
            setData(response.data);
        } catch (error) {
            console.error('Error fetching journal entries:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const toggleRow = (id: number) => {
        const newExpanded = new Set(expandedRows);
        if (newExpanded.has(id)) {
            newExpanded.delete(id);
        } else {
            newExpanded.add(id);
        }
        setExpandedRows(newExpanded);
    };

    const handleDownloadPDF = () => {
        const printUrl = route('double-entry.reports.journal-entry.print') +
            `?from_date=${fromDate}&to_date=${toDate}&status=${status}&download=pdf`;
        window.open(printUrl, '_blank');
    };

    const clearFilters = () => {
        setFromDate(financialYear?.year_start_date || '');
        setToDate(financialYear?.year_end_date || '');
        setStatus('');
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Date')}</label>
                        <DatePicker
                            value={fromDate}
                            onChange={setFromDate}
                            placeholder={t('Select from date')}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Date')}</label>
                        <DatePicker
                            value={toDate}
                            onChange={setToDate}
                            placeholder={t('Select to date')}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('All Status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=" ">{t('All Status')}</SelectItem>
                                <SelectItem value="posted">{t('Posted')}</SelectItem>
                                <SelectItem value="draft">{t('Draft')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                        {data.length > 0 && auth.user?.permissions?.includes('print-journal-entry') && (
                            <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data.length > 0 ? (
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[60vh] w-full">
                        <div className="min-w-[900px]">
                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold w-12"></th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Journal #')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Date')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Reference')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Description')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Debit')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Credit')}</th>
                                        <th className="px-4 py-3 text-center text-sm font-semibold">{t('Status')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map((entry) => (
                                        <>
                                            <tr key={entry.id} className="border-t hover:bg-gray-50 cursor-pointer" onClick={() => toggleRow(entry.id)}>
                                                <td className="px-4 py-3 text-sm">
                                                    {expandedRows.has(entry.id) ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                                </td>
                                                <td className="px-4 py-3 text-sm font-medium">{entry.journal_number}</td>
                                                <td className="px-4 py-3 text-sm">{formatDate(entry.date)}</td>
                                                <td className="px-4 py-3 text-sm">{entry.reference_type}</td>
                                                <td className="px-4 py-3 text-sm">{entry.description}</td>
                                                <td className="px-4 py-3 text-sm text-right">{formatCurrency(entry.total_debit)}</td>
                                                <td className="px-4 py-3 text-sm text-right">{formatCurrency(entry.total_credit)}</td>
                                                <td className="px-4 py-3 text-sm text-center">
                                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                                        entry.status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {t(entry.status === 'posted' ? 'Posted' : 'Draft')}
                                                    </span>
                                                    {!entry.is_balanced && (
                                                        <span className="px-2 py-1 rounded-full text-sm bg-red-100 text-red-800 ml-2">{t('Unbalanced')}</span>
                                                    )}
                                                </td>
                                            </tr>
                                            {expandedRows.has(entry.id) && (
                                                <tr>
                                                    <td colSpan={8} className="px-4 py-2 bg-gray-50">
                                                        <table className="w-full">
                                                            <thead>
                                                                <tr className="text-xs text-gray-600">
                                                                    <th className="px-4 py-2 text-left">{t('Account Code')}</th>
                                                                    <th className="px-4 py-2 text-left">{t('Account Name')}</th>
                                                                    <th className="px-4 py-2 text-left">{t('Description')}</th>
                                                                    <th className="px-4 py-2 text-right">{t('Debit')}</th>
                                                                    <th className="px-4 py-2 text-right">{t('Credit')}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {entry.items.map((item, idx) => (
                                                                    <tr key={idx} className="text-sm">
                                                                        <td className="px-4 py-2">{item.account_code}</td>
                                                                        <td className="px-4 py-2">{item.account_name}</td>
                                                                        <td className="px-4 py-2">{item.description}</td>
                                                                        <td className="px-4 py-2 text-right">{item.debit > 0 ? formatCurrency(item.debit) : '-'}</td>
                                                                        <td className="px-4 py-2 text-right">{item.credit > 0 ? formatCurrency(item.credit) : '-'}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            )}
                                        </>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Journal Entry Report')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
