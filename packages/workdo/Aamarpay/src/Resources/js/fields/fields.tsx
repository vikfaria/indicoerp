import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'aamarpay-gateway',
        order: 1640,
        component: (
            <SelectItem value="Aamarpay">{'Aamarpay'}</SelectItem>
        )
        
    }];
};