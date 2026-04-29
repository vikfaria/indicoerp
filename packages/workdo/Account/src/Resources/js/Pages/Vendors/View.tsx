import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Building2 } from 'lucide-react';
import { Vendor } from './types';

interface ViewProps {
    vendor: Vendor;
}

export default function View({ vendor }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Building2 className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Vendor Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{vendor.company_name}</p>
                    </div>
                </div>
            </DialogHeader>

            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Vendor Code')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.vendor_code}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Company Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.company_name}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Name')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.contact_person_name || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Email')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.contact_person_email || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Contact Person Mobile')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.contact_person_mobile || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Primary Email')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.primary_email || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Primary Mobile')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.primary_mobile || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Tax Number')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.tax_number || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Payment Terms')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.payment_terms || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Currency Code')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.currency_code || '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Credit Limit')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.credit_limit ? `$${Number(vendor.credit_limit).toFixed(2)}` : '-'}</p>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('User')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.user?.name || '-'}</p>
                    </div>
                </div>

                {vendor.billing_address && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Billing Address')}</label>
                        <div className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {vendor.billing_address.address && <p>{vendor.billing_address.address}</p>}
                            {vendor.billing_address.city && <p>{vendor.billing_address.city}, {vendor.billing_address.state} {vendor.billing_address.zip_code}</p>}
                            {vendor.billing_address.country && <p>{vendor.billing_address.country}</p>}
                        </div>
                    </div>
                )}

                {vendor.shipping_address && !vendor.same_as_billing && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Shipping Address')}</label>
                        <div className="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                            {vendor.shipping_address.address && <p>{vendor.shipping_address.address}</p>}
                            {vendor.shipping_address.city && <p>{vendor.shipping_address.city}, {vendor.shipping_address.state} {vendor.shipping_address.zip_code}</p>}
                            {vendor.shipping_address.country && <p>{vendor.shipping_address.country}</p>}
                        </div>
                    </div>
                )}

                {vendor.notes && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">{t('Notes')}</label>
                        <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded">{vendor.notes}</p>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}
