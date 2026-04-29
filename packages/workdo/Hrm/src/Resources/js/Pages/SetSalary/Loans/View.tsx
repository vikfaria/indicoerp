import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { DollarSign, FileText, Calendar, Tag, User, AlertCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/utils/helpers';

interface Loan {
    id: number;
    title: string;
    loan_type_id: number;
    type: string;
    amount: number;
    start_date?: string;
    end_date?: string;
    reason?: string;
    loan_type?: {
        name: string;
    };
}

interface ViewLoanProps {
    loan: Loan;
}

export default function View({ loan }: ViewLoanProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-blue-100 rounded-lg">
                        <DollarSign className="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Loan Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{loan.title}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Title')}
                            </label>
                            <p className="mt-1 font-medium">{loan.title || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Tag className="h-4 w-4" />
                                {t('Loan Type')}
                            </label>
                            <p className="mt-1 font-medium">{loan.loan_type?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <AlertCircle className="h-4 w-4" />
                                {t('Type')}
                            </label>
                            <div className="mt-1">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                    loan.type === 'fixed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                                }`}>
                                    {t(loan.type === 'fixed' ? 'Fixed' : 'Percentage')}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <DollarSign className="h-4 w-4" />
                                {t('Amount')}
                            </label>
                            <p className="mt-1 font-medium text-lg">
                                {loan.type === 'fixed' 
                                    ? formatCurrency(loan.amount) ||'0'
                                    : formatCurrency(loan.amount) ||'0%'
                                }
                            </p>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Start Date')}
                            </label>
                            <p className="mt-1 font-medium">
                                {loan.start_date ? formatDate(loan.start_date) : '-'}
                            </p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('End Date')}
                            </label>
                            <p className={`mt-1 font-medium ${loan.end_date && new Date(loan.end_date) < new Date() ? 'text-red-600' : ''}`}>
                                {loan.end_date ? formatDate(loan.end_date) : '-'}
                            </p>
                        </div>
                    </div>
                </div>
                
                {loan.reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Reason')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{loan.reason}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}