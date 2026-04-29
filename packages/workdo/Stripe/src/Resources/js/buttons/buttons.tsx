import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, isPackageActive, getPackageFavicon } from '@/utils/helpers';

export const paymentMethodBtn = (data?: any) => {

    const { t } = useTranslation();
    const { auth } = usePage().props as any;

    const stripeEnabled = getAdminSetting('stripe_enabled');

    if (stripeEnabled === 'on') {
        return [{
            id: 'stripe-payment',
            dataUrl: route('payment.stripe.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="stripe" id="stripe" />
                    <Label htmlFor="stripe" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Stripe')}</div>
                        </div>
                        <img src={getPackageFavicon('Stripe')} alt="Stripe" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    else {
        return [];
    }
};
