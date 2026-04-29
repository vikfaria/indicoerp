import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'paystack-gateway',
        order: 1530,
        component: (
            <SelectItem value="Paystack">{'Paystack'}</SelectItem>
        )
    }];
};