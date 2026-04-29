import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { BankTransfer } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

interface ViewProps {
    banktransfer: BankTransfer;
}

export default function View({ banktransfer }: ViewProps) {
    const { t } = useTranslation();

    const getStatusBadge = (status: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                status === 'completed' ? 'bg-green-100 text-green-800' : 
                status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                'bg-red-100 text-red-800'
            }`}>
                {t(status.charAt(0).toUpperCase() + status.slice(1))}
            </span>
        );
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Bank Transfer Details')}</DialogTitle>
            </DialogHeader>
            
            <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Transfer Number')}</Label>
                        <div className="mt-1 font-medium">{banktransfer.transfer_number}</div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Transfer Date')}</Label>
                        <div className="mt-1">{formatDate(banktransfer.transfer_date)}</div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('From Account')}</Label>
                        <div className="mt-1">
                            <div className="font-medium">{banktransfer.from_account.account_name}</div>
                            <div className="text-sm text-gray-500">{banktransfer.from_account.account_number}</div>
                        </div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('To Account')}</Label>
                        <div className="mt-1">
                            <div className="font-medium">{banktransfer.to_account.account_name}</div>
                            <div className="text-sm text-gray-500">{banktransfer.to_account.account_number}</div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Transfer Amount')}</Label>
                        <div className="mt-1 font-medium text-lg">{formatCurrency(banktransfer.transfer_amount)}</div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Transfer Charges')}</Label>
                        <div className="mt-1 font-medium">{formatCurrency(banktransfer.transfer_charges)}</div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Total Amount')}</Label>
                        <div className="mt-1 font-medium text-lg text-red-600">
                            {formatCurrency(banktransfer.transfer_amount + banktransfer.transfer_charges)}
                        </div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Status')}</Label>
                        <div className="mt-1">{getStatusBadge(banktransfer.status)}</div>
                    </div>
                </div>

                {banktransfer.reference_number && (
                    <div>
                        <Label className="text-sm font-medium text-gray-500">{t('Reference Number')}</Label>
                        <div className="mt-1 font-medium">{banktransfer.reference_number}</div>
                    </div>
                )}

                <div>
                    <Label className="text-sm font-medium text-gray-500">{t('Description')}</Label>
                    <div className="mt-1 p-3 bg-gray-50 rounded-md">{banktransfer.description}</div>
                </div>

                <div>
                    <Label className="text-sm font-medium text-gray-500">{t('Created At')}</Label>
                    <div className="mt-1 text-sm text-gray-600">{formatDate(banktransfer.created_at)}</div>
                </div>
            </div>
        </DialogContent>
    );
}