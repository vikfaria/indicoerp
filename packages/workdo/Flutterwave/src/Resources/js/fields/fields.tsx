import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'flutterwave-gateway',
        order: 1520,
        component: (
            <SelectItem value="Flutterwave">{'Flutterwave'}</SelectItem>
        )
    }];
};