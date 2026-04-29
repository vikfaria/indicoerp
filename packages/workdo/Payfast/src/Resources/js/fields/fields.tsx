import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'payfast-gateway',
        order: 1560,
        component: (
            <SelectItem value="Payfast">{'Payfast'}</SelectItem>
        )
        
    }];
};