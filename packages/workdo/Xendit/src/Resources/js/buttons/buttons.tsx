import { RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { getAdminSetting, getCompanySetting, getPackageFavicon } from '@/utils/helpers';

const AdminXenditEnabled = () => getAdminSetting('xendit_enabled') == 'on';
const xenditEnabled = () => getCompanySetting('xendit_enabled') == 'on';
const XenditFavicon = () => getPackageFavicon('Xendit');


export const paymentMethodBtn = (data?: any) => {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;

    if (AdminXenditEnabled()) {
        return [{
            id: 'xendit-payment',
            dataUrl: route('payment.xendit.store'),
            onFormSubmit: data?.onFormSubmit,
            component: (
                <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <RadioGroupItem value="xendit" id="xendit" />
                    <Label htmlFor="xendit" className="cursor-pointer flex items-center space-x-2">
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">{t('Xendit')}</div>
                        </div>
                        <img src={XenditFavicon()} alt="Xendit" className="h-10 w-10" />
                    </Label>
                </div>
            )
        }];
    }
    return [];
};
