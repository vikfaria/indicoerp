import { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import InvoiceAging from './InvoiceAging';
import BillAging from './BillAging';
import MozambiqueGoLiveReadiness from './MozambiqueGoLiveReadiness';
import FiscalClosing from './FiscalClosing';
import CustomerBalance from './CustomerBalance';
import VendorBalance from './VendorBalance';
import { ArrowRight, ShieldCheck } from 'lucide-react';

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
    const userPermissions = auth.user?.permissions || [];


    const tabs = [
        { id: 'invoice-aging', label: t('Invoice Aging'), permission: 'view-invoice-aging' },
        { id: 'bill-aging', label: t('Bill Aging'), permission: 'view-bill-aging' },
        { id: 'fiscal-compliance', label: t('Conformidade Fiscal (SCE)'), permissions: ['view-tax-summary', 'manage-account-reports'] },
        { id: 'mozambique-go-live-readiness', label: t('Go-Live Readiness'), permission: 'manage-account-reports' },
        { id: 'fiscal-closing', label: t('Fiscal Closing'), permission: 'manage-account-reports' },
        { id: 'customer-balance', label: t('Customer Balance'), permission: 'view-customer-balance' },
        { id: 'vendor-balance', label: t('Vendor Balance'), permission: 'view-vendor-balance' },
    ].filter((tab: any) => {
        if (tab.permission) {
            return userPermissions.includes(tab.permission);
        }

        if (Array.isArray(tab.permissions)) {
            return tab.permissions.some((permission: string) => userPermissions.includes(permission));
        }

        return true;
    });

    useEffect(() => {
        if (!tabs.some((tab) => tab.id === activeTab) && tabs.length > 0) {
            setActiveTab(tabs[0].id);
        }
    }, [activeTab, tabs]);

    const fiscalHubLinks = [
        { label: t('Mapa IVA'), description: t('Cálculo mensal de IVA e dedutibilidade.'), href: route('sce.tax.vat-map') },
        { label: t('IRPC'), description: t('Resultado fiscal, correcções e imposto do exercício.'), href: route('sce.tax.irpc') },
        { label: t('Retenções na Fonte'), description: t('Configuração e controlo de retenções por fornecedor.'), href: route('sce.tax.withholding') },
        { label: t('Declaração de Retenções'), description: t('Mapa mensal para pagamento e exportação.'), href: route('sce.tax.withholding.declaration.page') },
        { label: t('Modelo 20'), description: t('Mapa de apoio e mapeamento fiscal por conta.'), href: route('sce.tax.modelo20.page') },
        { label: t('Declaração Anual'), description: t('Consolidação anual fiscal e contabilística.'), href: route('sce.tax.annual-declaration.page') },
        { label: t('Perfil Fiscal e SAF-T'), description: t('Parâmetros fiscais, períodos e exportação SAF-T.'), href: route('sce.fiscal.index') },
    ];

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

                        <TabsContent value="fiscal-compliance" className="mt-4">
                            <Card className="border-dashed">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-xl">
                                        <ShieldCheck className="h-5 w-5 text-primary" />
                                        {t('Conformidade Fiscal Unificada')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t('Os relatórios fiscais foram centralizados no módulo Impostos/SCE para evitar duplicações e garantir a mesma base contabilística.')}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    {fiscalHubLinks.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className="group rounded-lg border bg-background p-4 transition-colors hover:border-primary/60 hover:bg-primary/5"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="font-medium">{item.label}</p>
                                                    <p className="mt-1 text-sm text-muted-foreground">{item.description}</p>
                                                </div>
                                                <ArrowRight className="mt-0.5 h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
                                            </div>
                                        </Link>
                                    ))}
                                </CardContent>
                            </Card>
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
