import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'midtrans-gateway',
        order: 1730,
        component: (
            <SelectItem value="Midtrans">{'Midtrans'}</SelectItem>
        )
    }];
};