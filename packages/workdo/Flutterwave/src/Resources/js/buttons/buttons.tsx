import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, isPackageActive, getPackageFavicon } from '@/utils/helpers';

const AdminFlutterwaveEnabled = () => getAdminSetting('flutterwave_enabled') == 'on';
const FlutterwaveFavicon = () => getPackageFavicon('Flutterwave');

export const paymentMethodBtn = (data?: any) => {

    const { t } = useTranslation();

    if (AdminFlutterwaveEnabled()) {
        return [{
            id: 'flutterwave-payment',
            dataUrl: route('payment.flutterwave.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="flutterwave" id="flutterwave" />
                    <Label htmlFor="flutterwave" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Flutterwave')}</div>
                        </div>
                        <img src={FlutterwaveFavicon()} alt="Flutterwave" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};