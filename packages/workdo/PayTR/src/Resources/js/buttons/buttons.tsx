import React from 'react';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminPayTREnabled = () => getAdminSetting('paytr_enabled') == 'on';
const paytrEnabled = () => getCompanySetting('paytr_enabled') == 'on';
const PayTRFavicon = () => getPackageFavicon('PayTR');

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();

    if (AdminPayTREnabled()) {
        return [{
            id: 'paytr-payment',
            dataUrl: route('payment.paytr.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="paytr" id="paytr" />
                    <Label htmlFor="paytr" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('PayTR')}</div>
                        </div>
                        <img src={PayTRFavicon()} alt="PayTR" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    else {
        return [];
    }
};