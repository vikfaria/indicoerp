import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import InvoiceAging from './InvoiceAging';
import BillAging from './BillAging';
import TaxSummary from './TaxSummary';
import MozambiqueFiscalMap from './MozambiqueFiscalMap';
import MozambiqueVatDeclaration from './MozambiqueVatDeclaration';
import MozambiqueGoLiveReadiness from './MozambiqueGoLiveReadiness';
import FiscalClosing from './FiscalClosing';
import CustomerBalance from './CustomerBalance';
import VendorBalance from './VendorBalance';

interface ReportsIndexProps {
    auth: {
        user?: {
            permissions?: string[];
        }
    }
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    }
}

export default function Index() {
    const { t } = useTranslation();
    const { auth, financialYear } = usePage<ReportsIndexProps>().props;
    const [activeTab, setActiveTab] = useState('invoice-aging');


    const tabs = [
        { id: 'invoice-aging', label: t('Invoice Aging'), permission: 'view-invoice-aging' },
        { id: 'bill-aging', label: t('Bill Aging'), permission: 'view-bill-aging' },
        { id: 'tax-summary', label: t('Tax Summary'), permission: 'view-tax-summary' },
        { id: 'mozambique-fiscal-map', label: t('Mozambique Fiscal Map'), permission: 'view-tax-summary' },
        { id: 'mozambique-vat-declaration', label: t('Mozambique VAT Declaration'), permission: 'view-tax-summary' },
        { id: 'mozambique-go-live-readiness', label: t('Go-Live Readiness'), permission: 'manage-account-reports' },
        { id: 'fiscal-closing', label: t('Fiscal Closing'), permission: 'manage-account-reports' },
        { id: 'customer-balance', label: t('Customer Balance'), permission: 'view-customer-balance' },
        { id: 'vendor-balance', label: t('Vendor Balance'), permission: 'view-vendor-balance' },
    ].filter(tab => auth.user?.permissions?.includes(tab.permission));

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Accounting'), url: route('account.index')},
                { label: t('Reports') }
            ]}
            pageTitle={t('Reports')}
        >
            <Head title={t('Reports')} />

            <Card className="shadow-sm">
                <CardContent className="p-6">
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList className="w-full justify-start overflow-x-auto overflow-y-hidden h-auto p-1">
                            {tabs.map(tab => (
                                <TabsTrigger key={tab.id} value={tab.id} className="whitespace-nowrap flex-shrink-0">
                                    {tab.label}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        <TabsContent value="invoice-aging" className="mt-4">
                            <InvoiceAging financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="bill-aging" className="mt-4">
                            <BillAging financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="tax-summary" className="mt-4">
                            <TaxSummary financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="mozambique-fiscal-map" className="mt-4">
                            <MozambiqueFiscalMap financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="mozambique-vat-declaration" className="mt-4">
                            <MozambiqueVatDeclaration financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="mozambique-go-live-readiness" className="mt-4">
                            <MozambiqueGoLiveReadiness />
                        </TabsContent>

                        <TabsContent value="fiscal-closing" className="mt-4">
                            <FiscalClosing financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="customer-balance" className="mt-4">
                            <CustomerBalance financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="vendor-balance" className="mt-4">
                            <VendorBalance financialYear={financialYear} />
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
