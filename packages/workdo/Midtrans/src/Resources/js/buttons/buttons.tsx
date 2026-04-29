import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;

    const midtransEnabled = getAdminSetting('midtrans_enabled');
    if (midtransEnabled === 'on') {
        return [{
            id: 'midtrans-payment',
            dataUrl: route('payment.midtrans.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="midtrans" id="midtrans" />
                    <Label htmlFor="midtrans" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Midtrans')}</div>
                        </div>
                        <img src={getPackageFavicon('Midtrans')} alt="Midtrans" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    else {
        return [];
    }
};
