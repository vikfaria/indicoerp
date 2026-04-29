import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'paypal-gateway',
        order: 2,
        component: (
            <SelectItem value="Paypal">{'Paypal'}</SelectItem>
        )
    }];
};
