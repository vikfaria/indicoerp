import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { CreditCard } from 'lucide-react';
import { BankAccount } from './types';
import { formatCurrency } from '@/utils/helpers';
import { getAccountTypeLabel, isCashAccountType } from './account-type';

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
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {bankaccount.branch?.branch_name || bankaccount.branch_name || '-'}
                        </p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Account Type')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {getAccountTypeLabel(bankaccount.account_type)}
                        </p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Cash Account')}</label>
                        <p className="text-sm">
                            <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                                isCashAccountType(bankaccount.account_type)
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-gray-100 text-gray-700'
                            }`}>
                                {isCashAccountType(bankaccount.account_type) ? t('Yes') : t('No')}
                            </span>
                        </p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Electronic Money Account')}</label>
                        <p className="text-sm">
                            <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                                bankaccount.is_electronic_money_account
                                    ? 'bg-blue-100 text-blue-800'
                                    : 'bg-gray-100 text-gray-700'
                            }`}>
                                {bankaccount.is_electronic_money_account ? t('Yes') : t('No')}
                            </span>
                        </p>
                    </div>
                    {bankaccount.is_electronic_money_account && (
                        <>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Electronic Money Entity')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.electronic_money_entity || '-'}</p>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Electronic Money Account Level')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.electronic_money_level || '-'}</p>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Daily Limit (MZN)')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                                    {bankaccount.electronic_money_daily_limit_mzn !== undefined && bankaccount.electronic_money_daily_limit_mzn !== null
                                        ? formatCurrency(bankaccount.electronic_money_daily_limit_mzn)
                                        : '-'}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Monthly Limit (MZN)')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                                    {bankaccount.electronic_money_monthly_limit_mzn !== undefined && bankaccount.electronic_money_monthly_limit_mzn !== null
                                        ? formatCurrency(bankaccount.electronic_money_monthly_limit_mzn)
                                        : '-'}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Enterprise Limit Exempt')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                                    {bankaccount.electronic_money_limit_exempt_for_enterprise ? t('Yes') : t('No')}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">{t('Electronic Money Account Purpose')}</label>
                                <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{bankaccount.electronic_money_account_purpose || '-'}</p>
                            </div>
                        </>
                    )}
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
