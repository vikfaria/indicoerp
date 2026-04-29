import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;

    const benefitEnabled = getAdminSetting('benefit_enabled');
    if (benefitEnabled === 'on') {
        return [{
            id: 'benefit-payment',
            dataUrl: route('payment.benefit.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="benefit" id="benefit" />
                    <Label htmlFor="benefit" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Benefit')}</div>
                        </div>
                        <img src={getPackageFavicon('Benefit')} alt="Benefit" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    else {
        return [];
    }
};