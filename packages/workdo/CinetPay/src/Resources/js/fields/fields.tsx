import { SelectItem } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';

export const paymentGateway = () => {
    const { t } = useTranslation();
    return [{
        id: 'cinetpay-gateway',
        order: 1820,
        component: (
            <SelectItem value="CinetPay">{t('CinetPay')}</SelectItem>
        )
    }];
};