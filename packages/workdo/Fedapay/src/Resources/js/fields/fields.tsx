import { SelectItem } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';

export const paymentGateway = () => {
    const { t } = useTranslation();
    return [{
        id: 'fedapay-gateway',
        order: 1810,
        component: (
            <SelectItem value="Fedapay">{t('FedaPay')}</SelectItem>
        )
    }];
};
