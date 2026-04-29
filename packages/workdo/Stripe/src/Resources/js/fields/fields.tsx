import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'stripe-gateway',
        order: 1,
        component: (
            <SelectItem value="Stripe">{'Stripe'}</SelectItem>
        )
    }];
};
