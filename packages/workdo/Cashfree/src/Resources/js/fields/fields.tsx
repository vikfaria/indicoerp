import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'cashfree-gateway',
        order: 1660,
        component: (
            <SelectItem value="Cashfree">{'Cashfree'}</SelectItem>
        )
    }];
};