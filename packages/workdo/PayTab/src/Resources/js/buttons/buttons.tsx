import React from 'react';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminPayTabEnabled = () => getAdminSetting('paytab_payment_is_on') == 'on';
const PayTabFavicon = () => getPackageFavicon('PayTab');

export const paymentMethodBtn = (data?: any) => {

    const { t } = useTranslation();

    if (AdminPayTabEnabled()) {
        return [{
            id: 'paytab-payment',
            dataUrl: route('payment.paytab.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="paytab" id="paytab" />
                    <Label htmlFor="paytab" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('PayTab')}</div>
                        </div>
                        <img src={PayTabFavicon()} alt="PayTab" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};
