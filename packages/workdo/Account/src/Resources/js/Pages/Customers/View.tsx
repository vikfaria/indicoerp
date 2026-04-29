import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Building2 } from 'lucide-react';
import { Customer } from './types';

interface ViewProps {
    customer: Customer;
}

export default function View({ customer }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Building2 className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Customer Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{customer.company_name}</p>
                    </div>
                </div>
            </DialogHeader>

            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Customer Code')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.customer_code}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Company Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.company_name}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.contact_person_name || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Email')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.contact_person_email || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Mobile')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.contact_person_mobile || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Tax Number')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.tax_number || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Payment Terms')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.payment_terms || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('User')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.user?.name || '-'}</p>
                    </div>
                </div>

                {customer.billing_address && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Billing Address')}</label>
                        <div className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {customer.billing_address.address && <p>{customer.billing_address.address}</p>}
                            {customer.billing_address.city && <p>{customer.billing_address.city}, {customer.billing_address.state} {customer.billing_address.zip_code}</p>}
                            {customer.billing_address.country && <p>{customer.billing_address.country}</p>}
                        </div>
                    </div>
                )}

                {customer.shipping_address && !customer.same_as_billing && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Shipping Address')}</label>
                        <div className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {customer.shipping_address.address && <p>{customer.shipping_address.address}</p>}
                            {customer.shipping_address.city && <p>{customer.shipping_address.city}, {customer.shipping_address.state} {customer.shipping_address.zip_code}</p>}
                            {customer.shipping_address.country && <p>{customer.shipping_address.country}</p>}
                        </div>
                    </div>
                )}

                {customer.notes && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Notes')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{customer.notes}</p>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}
