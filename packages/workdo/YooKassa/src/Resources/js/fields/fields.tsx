import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'yookassa-gateway',
        order: 1570,
        component: (
            <SelectItem value="YooKassa">{'YooKassa'}</SelectItem>
        )
    }];
};