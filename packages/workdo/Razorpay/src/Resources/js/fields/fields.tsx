import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'razorpay-gateway',
        order: 1540,
        component: (
            <SelectItem value="Razorpay">{'Razorpay'}</SelectItem>
        )
    }];
};