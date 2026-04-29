import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { CreditCard } from 'lucide-react';
import { BankAccount } from './types';
import { formatCurrency } from '@/utils/helpers';

interface ViewProps {
    bankaccount: BankAccount;
}

export default function View({ bankaccount }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <CreditCard className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Bank Account Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{bankaccount.account_name}</p>
                    </div>
                </div>
            </DialogHeader>

            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Account Number')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.account_number}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Account Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.account_name}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Bank Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.bank_name}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Branch Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.branch_name || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Account Type')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {bankaccount.account_type === '0' ? 'Checking' :
                             bankaccount.account_type === '1' ? 'Savings' :
                             bankaccount.account_type === '2' ? 'Credit' : 'Loan'}
                        </p>
                    </div>
                    {/* <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Payment Gateway')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.payment_gateway || '-'}</p>
                    </div> */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Opening Balance')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{formatCurrency(bankaccount.opening_balance || 0)}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Current Balance')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{formatCurrency(bankaccount.current_balance || 0)}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('IBAN')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.iban || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('SWIFT Code')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.swift_code || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Routing Number')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.routing_number || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('GL Account')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.gl_account?.account_name || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Status')}</label>
                        <p className="text-sm">
                            <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                                bankaccount.is_active
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            }`}>
                                {bankaccount.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    );
}
