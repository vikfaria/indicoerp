import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import InputError from "@/components/ui/input-error";
import SystemSetupSidebar from "../SystemSetupSidebar";
import { Trash2 } from "lucide-react";

interface ChartAccount {
    id: number;
    account_code: string;
    account_name: string;
}

interface Mapping {
    id: number;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    notes: string | null;
    vat_output_account?: ChartAccount | null;
    vat_input_account?: ChartAccount | null;
    withholding_payable_account?: ChartAccount | null;
    withholding_receivable_account?: ChartAccount | null;
    irpc_expense_account?: ChartAccount | null;
}

interface PageProps {
    chartAccounts: ChartAccount[];
    mappings: Mapping[];
    auth: {
        user: {
            permissions?: string[];
        };
    };
}

export default function MozambiqueTaxAccountMappingsIndex() {
    const { t } = useTranslation();
    const { chartAccounts, mappings, auth } = usePage<PageProps>().props;
    const canEdit = auth.user?.permissions?.includes('edit-chart-of-accounts') ?? false;
    const canDelete = auth.user?.permissions?.includes('delete-chart-of-accounts') ?? false;
    const selectClassName = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm';

    const form = useForm({
        vat_output_account_id: '',
        vat_input_account_id: '',
        withholding_payable_account_id: '',
        withholding_receivable_account_id: '',
        irpc_expense_account_id: '',
        effective_from: '',
        effective_to: '',
        is_active: true,
        notes: '',
    });

    const accountLabel = (account?: ChartAccount | null): string => {
        if (!account) {
            return '-';
        }

        return `${account.account_code} - ${account.account_name}`;
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Accounting'), url: route('account.index') },
                { label: t('System Setup') },
                { label: t('Mozambique Tax Mapping') },
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Mozambique Tax Mapping')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="mozambique-tax-account-mappings" />
                </div>

                <div className="flex-1 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Fiscal Account Mapping')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-2 gap-4"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    form.post(route('account.mozambique-tax-account-mappings.store'));
                                }}
                            >
                                <div>
                                    <Label>{t('VAT Output Account')}</Label>
                                    <select className={selectClassName} value={form.data.vat_output_account_id} onChange={(e) => form.setData('vat_output_account_id', e.target.value)}>
                                        <option value="">{t('None')}</option>
                                        {chartAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_code} - {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.vat_output_account_id} />
                                </div>

                                <div>
                                    <Label>{t('VAT Input Account')}</Label>
                                    <select className={selectClassName} value={form.data.vat_input_account_id} onChange={(e) => form.setData('vat_input_account_id', e.target.value)}>
                                        <option value="">{t('None')}</option>
                                        {chartAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_code} - {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.vat_input_account_id} />
                                </div>

                                <div>
                                    <Label>{t('Withholding Payable Account')}</Label>
                                    <select className={selectClassName} value={form.data.withholding_payable_account_id} onChange={(e) => form.setData('withholding_payable_account_id', e.target.value)}>
                                        <option value="">{t('None')}</option>
                                        {chartAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_code} - {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.withholding_payable_account_id} />
                                </div>

                                <div>
                                    <Label>{t('Withholding Receivable Account')}</Label>
                                    <select className={selectClassName} value={form.data.withholding_receivable_account_id} onChange={(e) => form.setData('withholding_receivable_account_id', e.target.value)}>
                                        <option value="">{t('None')}</option>
                                        {chartAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_code} - {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.withholding_receivable_account_id} />
                                </div>

                                <div>
                                    <Label>{t('IRPC Expense Account')}</Label>
                                    <select className={selectClassName} value={form.data.irpc_expense_account_id} onChange={(e) => form.setData('irpc_expense_account_id', e.target.value)}>
                                        <option value="">{t('None')}</option>
                                        {chartAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_code} - {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.irpc_expense_account_id} />
                                </div>

                                <div>
                                    <Label>{t('Effective From')}</Label>
                                    <Input type="date" value={form.data.effective_from} onChange={(e) => form.setData('effective_from', e.target.value)} />
                                    <InputError message={form.errors.effective_from} />
                                </div>

                                <div>
                                    <Label>{t('Effective To')}</Label>
                                    <Input type="date" value={form.data.effective_to} onChange={(e) => form.setData('effective_to', e.target.value)} />
                                    <InputError message={form.errors.effective_to} />
                                </div>

                                <div>
                                    <Label>{t('Notes')}</Label>
                                    <Input value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                                    <InputError message={form.errors.notes} />
                                </div>

                                <div className="md:col-span-2 flex items-center justify-between">
                                    <label className="inline-flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.data.is_active}
                                            onChange={(e) => form.setData('is_active', e.target.checked)}
                                        />
                                        {t('Active')}
                                    </label>

                                    <Button type="submit" disabled={!canEdit || form.processing}>
                                        {t('Save Mapping')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Configured Mappings')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Effective')}</th>
                                            <th className="text-left py-2">{t('VAT Output')}</th>
                                            <th className="text-left py-2">{t('VAT Input')}</th>
                                            <th className="text-left py-2">{t('Withholding Payable')}</th>
                                            <th className="text-left py-2">{t('Withholding Receivable')}</th>
                                            <th className="text-left py-2">{t('IRPC Expense')}</th>
                                            <th className="text-left py-2">{t('Active')}</th>
                                            <th className="text-left py-2">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {mappings.map((mapping) => (
                                            <tr key={mapping.id} className="border-b align-top">
                                                <td className="py-2">{mapping.effective_from} - {mapping.effective_to || '∞'}</td>
                                                <td className="py-2">{accountLabel(mapping.vat_output_account)}</td>
                                                <td className="py-2">{accountLabel(mapping.vat_input_account)}</td>
                                                <td className="py-2">{accountLabel(mapping.withholding_payable_account)}</td>
                                                <td className="py-2">{accountLabel(mapping.withholding_receivable_account)}</td>
                                                <td className="py-2">{accountLabel(mapping.irpc_expense_account)}</td>
                                                <td className="py-2">{mapping.is_active ? t('Yes') : t('No')}</td>
                                                <td className="py-2">
                                                    {canDelete && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => router.delete(route('account.mozambique-tax-account-mappings.destroy', mapping.id))}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {mappings.length === 0 && (
                                            <tr>
                                                <td className="py-4 text-center text-muted-foreground" colSpan={8}>
                                                    {t('No mapping configured')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
