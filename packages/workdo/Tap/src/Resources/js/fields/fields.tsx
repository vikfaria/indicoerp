import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'tap-gateway',
        order: 1750,
        component: (
            <SelectItem value="Tap">{'Tap'}</SelectItem>
        )
        
    }];
};