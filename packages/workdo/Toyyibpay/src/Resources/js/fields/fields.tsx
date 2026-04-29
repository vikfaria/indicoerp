import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'toyyibpay-gateway',
        order: 1,
        component: (
            <SelectItem value="Toyyibpay">{'Toyyibpay'}</SelectItem>
        )
    }];
};