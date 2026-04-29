import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'coingate-gateway',
        order: 1670,
        component: (
            <SelectItem value="Coingate">{'Coingate'}</SelectItem>
        )
    }];
};