import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();
    const coingateEnabled = getAdminSetting('coingate_enabled');

    if (coingateEnabled === 'on') {
        return [{
            id: 'coingate-payment',
            dataUrl: route('plan.payment.coingate.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="coingate" id="coingate" />
                    <Label htmlFor="coingate" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Coingate')}</div>
                        </div>
                        <img src={getPackageFavicon('Coingate')} alt="Coingate" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};
