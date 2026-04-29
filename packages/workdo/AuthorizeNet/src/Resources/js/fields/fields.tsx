import { SelectItem } from '@/components/ui/select';

export const paymentGateway = () => {
    return [{
        id: 'authorizenet-gateway',
        order: 1780,
        component: (
            <SelectItem value="AuthorizeNet">{'AuthorizeNet'}</SelectItem>
        )
    }];
};