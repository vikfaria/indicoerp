import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'benefit-gateway',
        order: 1650,
        component: (
            <SelectItem value="Benefit">{'Benefit'}</SelectItem>
        )
    }];
};