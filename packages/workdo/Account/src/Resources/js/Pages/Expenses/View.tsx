import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatCurrency } from '@/utils/helpers';

interface Expense {
    id: number;
    expense_number: string;
    expense_date: string;
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

interface ShowExpenseProps {
    expense: Expense;
}

export default function Show({ expense }: ShowExpenseProps) {
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
                <DialogTitle>{t('Expense Details')} - {expense.expense_number}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6 mt-3">
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-4">
                            <div>
                                <span className="font-semibold">{t('Expense Number')}</span>
                                <p className="mt-1 text-gray-500">{expense.expense_number}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Expense Date')}</span>
                                <p className="mt-1 text-gray-500">{formatDate(expense.expense_date)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Category')}</span>
                                <p className="mt-1 text-gray-500">{expense.category?.category_name || '-'}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Bank Account')}</span>
                                <p className="mt-1 text-gray-500">{expense.bank_account?.account_name || '-'}</p>
                            </div>
                            {expense.chart_of_account && (
                                <div>
                                    <span className="font-semibold">{t('Chart of Account')}</span>
                                    <p className="mt-1 text-gray-500">{expense.chart_of_account.account_code} - {expense.chart_of_account.account_name}</p>
                                </div>
                            )}
                            <div>
                                <span className="font-semibold">{t('Amount')}</span>
                                <p className="mt-1 text-lg font-bold text-red-600">{formatCurrency(expense.amount)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Status')}</span>
                                <div className="mt-1">{getStatusBadge(expense.status)}</div>
                            </div>
                            {expense.reference_number && (
                                <div>
                                    <span className="font-semibold">{t('Reference Number')}</span>
                                    <p className="mt-1 text-gray-500">{expense.reference_number}</p>
                                </div>
                            )}
                            {expense.approved_by && (
                                <div>
                                    <span className="font-semibold">{t('Approved By')}</span>
                                    <p className="mt-1 text-gray-500">{expense.approved_by.name}</p>
                                </div>
                            )}
                        </div>
                        {expense.description && (
                            <div className="text-sm mt-4">
                                <span className="font-semibold">{t('Description')}</span>
                                <p className="mt-1 p-3 bg-gray-50 rounded text-sm">{expense.description}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DialogContent>
    );
}
