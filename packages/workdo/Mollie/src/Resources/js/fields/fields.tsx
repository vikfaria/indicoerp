import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'mollie-gateway',
        order: 1535,
        component: (
            <SelectItem value="Mollie">{'Mollie'}</SelectItem>
        )
        
    }];
};