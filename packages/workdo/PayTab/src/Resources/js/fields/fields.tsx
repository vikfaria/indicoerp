import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'paytab-gateway',
        order: 1580,
        component: (
            <SelectItem value="PayTab">{'PayTab'}</SelectItem>
        )
    }];
};