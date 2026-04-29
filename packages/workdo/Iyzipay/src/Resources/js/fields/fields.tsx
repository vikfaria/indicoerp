import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'iyzipay-gateway',
        order: 1620,
        component: (
            <SelectItem value="Iyzipay">{'Iyzipay'}</SelectItem>
        )
    }];
};