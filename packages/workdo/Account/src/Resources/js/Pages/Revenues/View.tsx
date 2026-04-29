    import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatCurrency } from '@/utils/helpers';

interface Revenue {
    id: number;
    revenue_number: string;
    revenue_date: string;
    category: { id: number; category_name: string };
    bank_account: { id: number; account_name: string };
    chart_of_account?: { id: number; account_code: string; account_name: string };
    amount: string;
    description: string;
    reference_number: string;
    status: 'draft' | 'approved' | 'posted';
    approved_by: { id: number; name: string } | null;
    creator: { id: number; name: string };
    created_at: string;
}

interface ShowRevenueProps {
    revenue: Revenue;
}

export default function Show({ revenue }: ShowRevenueProps) {
    const { t } = useTranslation();

    const getStatusBadge = (status: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                status === 'posted' ? 'bg-green-100 text-green-800' :
                status === 'approved' ? 'bg-blue-100 text-blue-800' :
                'bg-gray-100 text-gray-800'
            }`}>
                {status === 'posted' ? t('Posted') :
                 status === 'approved' ? t('Approved') :
                 t('Draft')}
            </span>
        );
    };

    return (
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Revenue Details')} - {revenue.revenue_number}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6 mt-3">
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-4">
                            <div>
                                <span className="font-semibold">{t('Revenue Number')}</span>
                                <p className="mt-1 text-gray-500">{revenue.revenue_number}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Revenue Date')}</span>
                                <p className="mt-1 text-gray-500">{formatDate(revenue.revenue_date)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Category')}</span>
                                <p className="mt-1 text-gray-500">{revenue.category?.category_name || '-'}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Bank Account')}</span>
                                <p className="mt-1 text-gray-500">{revenue.bank_account?.account_name || '-'}</p>
                            </div>
                            {revenue.chart_of_account && (
                                <div>
                                    <span className="font-semibold">{t('Chart of Account')}</span>
                                    <p className="mt-1 text-gray-500">{revenue.chart_of_account.account_code} - {revenue.chart_of_account.account_name}</p>
                                </div>
                            )}
                            <div>
                                <span className="font-semibold">{t('Amount')}</span>
                                <p className="mt-1 text-lg font-bold text-green-600">{formatCurrency(revenue.amount)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Status')}</span>
                                <div className="mt-1">{getStatusBadge(revenue.status)}</div>
                            </div>
                            {revenue.reference_number && (
                                <div>
                                    <span className="font-semibold">{t('Reference Number')}</span>
                                    <p className="mt-1 text-gray-500">{revenue.reference_number}</p>
                                </div>
                            )}
                            {revenue.approved_by && (
                                <div>
                                    <span className="font-semibold">{t('Approved By')}</span>
                                    <p className="mt-1 text-gray-500">{revenue.approved_by.name}</p>
                                </div>
                            )}
                        </div>
                        {revenue.description && (
                            <div className=" text-sm mt-4">
                                <span className="font-semibold">{t('Description')}</span>
                                <p className="mt-1 p-3 bg-gray-50 rounded text-sm">{revenue.description}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DialogContent>
    );
}
