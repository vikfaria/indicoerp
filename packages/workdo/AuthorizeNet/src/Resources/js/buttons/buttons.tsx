import React from 'react';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminAuthorizeNetEnabled = () => getAdminSetting('authorizenet_enabled') == 'on';
const AuthorizeNetFavicon = () => getPackageFavicon('AuthorizeNet');


export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();

    if (AdminAuthorizeNetEnabled()) {
        return [{
            id: 'authorizenet-payment',
            dataUrl: route('payment.authorizenet.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="authorizenet" id="authorizenet" />
                    <Label htmlFor="authorizenet" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('AuthorizeNet')}</div>
                        </div>
                        <img src={AuthorizeNetFavicon()} alt="AuthorizeNet" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};