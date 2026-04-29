import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminAamarpayEnabled = () => getAdminSetting('aamarpay_enabled') == 'on';
const aamarpayEnabled = () => getCompanySetting('aamarpay_enabled') == 'on';
const AamarpayFavicon = () => getPackageFavicon('Aamarpay');

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();

    if (AdminAamarpayEnabled()) {
        return [{
            id: 'aamarpay-payment',
            dataUrl: route('payment.aamarpay.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="aamarpay" id="aamarpay" />
                    <Label htmlFor="aamarpay" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Aamarpay')}</div>
                        </div>
                        <img src={AamarpayFavicon()} alt="Aamarpay" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    else {
        return [];
    }
};
