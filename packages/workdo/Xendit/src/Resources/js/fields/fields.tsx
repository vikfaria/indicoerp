import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {

    return [{
        id: 'xendit-gateway',
        order: 1740,
        component: (
            <SelectItem value="Xendit">{'Xendit'}</SelectItem>
        )
    }];
};