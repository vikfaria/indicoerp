import React from 'react';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminRazorpayEnabled = () => getAdminSetting('razorpay_enabled') == 'on';
const RazorpayFavicon = () => getPackageFavicon('Razorpay');

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();

    if (AdminRazorpayEnabled()) {
        return [{
            id: 'razorpay-payment',
            dataUrl: route('payment.razorpay.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="razorpay" id="razorpay" />
                    <Label htmlFor="razorpay" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Razorpay')}</div>
                        </div>
                        <img src={RazorpayFavicon()} alt="Razorpay" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};
