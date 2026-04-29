import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { FileText } from "lucide-react";
import GeneralLedger from './GeneralLedger';
import AccountStatement from './AccountStatement';
import JournalEntry from './JournalEntry';
import AccountBalance from './AccountBalance';
import CashFlow from './CashFlow';
import ExpenseReport from './ExpenseReport';

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
    const [activeTab, setActiveTab] = useState('journal-entry');

    const tabs = [
        { id: 'journal-entry', label: t('Journal Entry'), permission: 'view-journal-entry' },
        { id: 'general-ledger', label: t('General Ledger'), permission: 'view-general-ledger' },
        { id: 'account-statement', label: t('Account Statement'), permission: 'view-account-statement' },
        { id: 'account-balance', label: t('Account Balance'), permission: 'view-account-balance' },
        { id: 'cash-flow', label: t('Cash Flow'), permission: 'view-cash-flow' },
        { id: 'expense-report', label: t('Expense Report'), permission: 'view-expense-report' },
    ].filter(tab => auth.user?.permissions?.includes(tab.permission));

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Double Entry') },
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

                        <TabsContent value="journal-entry" className="mt-4">
                            <JournalEntry financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="general-ledger" className="mt-4">
                            <GeneralLedger financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="account-statement" className="mt-4">
                            <AccountStatement financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="account-balance" className="mt-4">
                            <AccountBalance financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="cash-flow" className="mt-4">
                            <CashFlow financialYear={financialYear} />
                        </TabsContent>

                        <TabsContent value="expense-report" className="mt-4">
                            <ExpenseReport financialYear={financialYear} />
                        </TabsContent>

                        {tabs.filter(tab => !['general-ledger', 'account-statement', 'journal-entry', 'account-balance', 'cash-flow', 'expense-report'].includes(tab.id)).map(tab => (
                            <TabsContent key={tab.id} value={tab.id} className="mt-4">
                                <div className="flex flex-col items-center justify-center py-12 text-center">
                                    <FileText className="h-16 w-16 text-gray-400 mb-4" />
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        {tab.label}
                                    </h3>
                                    <p className="text-sm text-gray-500">
                                        {t('Coming soon...')}
                                    </p>
                                </div>
                            </TabsContent>
                        ))}
                    </Tabs>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
