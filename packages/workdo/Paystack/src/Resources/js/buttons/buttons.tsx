import React from 'react';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminPaystackEnabled = () => getAdminSetting('paystack_enabled') == 'on';
const PaystackFavicon = () => getPackageFavicon('Paystack');

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();
    
    if (AdminPaystackEnabled()) {
        return [{
            id: 'paystack-payment',
            dataUrl: route('payment.paystack.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="paystack" id="paystack" />
                    <Label htmlFor="paystack" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Paystack')}</div>
                        </div>
                        <img src={PaystackFavicon()} alt="Paystack" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};
