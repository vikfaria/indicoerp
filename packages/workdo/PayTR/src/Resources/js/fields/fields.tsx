import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'paytr-gateway',
        order: 1630,
        component: (
            <SelectItem value="PayTR">{'PayTR'}</SelectItem>
        )
    }];
};