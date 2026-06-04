import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { Download, RefreshCw, ArrowLeftRight, FileSearch } from 'lucide-react';
import { toast } from 'sonner';

interface ExchangeOperation {
    payment_id: number;
    payment_type: string;
    direction: 'outbound' | 'inbound';
    operation_type: string;
    reference: string;
    date: string;
    counterparty: string;
    counterparty_country: string;
    counterparty_residency_status: string;
    currency_code: string;
    foreign_amount: number;
    exchange_rate: number;
    amount_mzn: number;
    status: string;
    is_export_receipt: boolean;
    repatriation_status: string;
    repatriated_amount_mzn: number;
    export_reference?: string;
    intermediary_bank?: string;
    fx_compliance_reference?: string;
    is_international: boolean;
    domestic_fx_violation: boolean;
    missing_fx_documentation: boolean;
    dossier_required?: boolean;
    dossier_complete?: boolean;
    dossier_missing_documents?: string[];
}

interface ExchangeControlPayload {
    from_date: string;
    to_date: string;
    summary: {
        total_operations: number;
        outbound_count: number;
        inbound_count: number;
        outbound_amount_mzn: number;
        inbound_amount_mzn: number;
        domestic_fx_violations: number;
        missing_fx_documentation: number;
        missing_dossier_count: number;
        completed_dossier_count: number;
        pending_repatriation_count: number;
        completed_repatriation_count: number;
    };
    outbound_payments: ExchangeOperation[];
    inbound_receipts: ExchangeOperation[];
    domestic_fx_violations: ExchangeOperation[];
    missing_documentation: ExchangeOperation[];
    missing_dossiers: ExchangeOperation[];
    completed_dossiers: ExchangeOperation[];
    pending_repatriation: ExchangeOperation[];
    completed_repatriation: ExchangeOperation[];
}

const badgeClass = (flag: boolean): string => (flag ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800');

interface RepatriationFormState {
    direction: 'inbound';
    payment_id: number | null;
    repatriation_status: 'pending' | 'partial' | 'completed';
    repatriated_amount_mzn: string;
    fx_compliance_reference: string;
    export_reference: string;
    intermediary_bank: string;
    receipt_origin_country: string;
}

interface DossierFormState {
    direction: 'outbound' | 'inbound';
    payment_id: number | null;
    contract_reference: string;
    invoice_reference: string;
    transport_document_reference: string;
    customs_declaration_reference: string;
    bank_settlement_reference: string;
    withholding_receipt_reference: string;
    fx_authorization_reference: string;
    correspondence_reference: string;
    notes: string;
}

const emptyRepatriationForm = (): RepatriationFormState => ({
    direction: 'inbound',
    payment_id: null,
    repatriation_status: 'pending',
    repatriated_amount_mzn: '',
    fx_compliance_reference: '',
    export_reference: '',
    intermediary_bank: '',
    receipt_origin_country: '',
});

const emptyDossierForm = (): DossierFormState => ({
    direction: 'outbound',
    payment_id: null,
    contract_reference: '',
    invoice_reference: '',
    transport_document_reference: '',
    customs_declaration_reference: '',
    bank_settlement_reference: '',
    withholding_receipt_reference: '',
    fx_authorization_reference: '',
    correspondence_reference: '',
    notes: '',
});

export default function ExchangeControlReport() {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [data, setData] = useState<ExchangeControlPayload | null>(null);
    const [loading, setLoading] = useState(false);
    const [repatriationRow, setRepatriationRow] = useState<ExchangeOperation | null>(null);
    const [dossierRow, setDossierRow] = useState<ExchangeOperation | null>(null);
    const [repatriationSaving, setRepatriationSaving] = useState(false);
    const [dossierSaving, setDossierSaving] = useState(false);
    const [repatriationForm, setRepatriationForm] = useState<RepatriationFormState>(emptyRepatriationForm);
    const [dossierForm, setDossierForm] = useState<DossierFormState>(emptyDossierForm);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            { label: t('Operations'), value: data.summary.total_operations },
            { label: t('Outbound'), value: data.summary.outbound_count },
            { label: t('Inbound'), value: data.summary.inbound_count },
            { label: t('FX Violations'), value: data.summary.domestic_fx_violations },
            { label: t('Missing Docs'), value: data.summary.missing_fx_documentation },
            { label: t('Pending Repatriation'), value: data.summary.pending_repatriation_count },
        ];
    }, [data, t]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-exchange-control-report'), {
                params: {
                    from_date: fromDate,
                    to_date: toDate,
                },
            });

            setData(response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleExport = () => {
        window.location.href = route('account.reports.mozambique-exchange-control-report.export', {
            from_date: fromDate,
            to_date: toDate,
        });
    };

    const openRepatriationDialog = (row: ExchangeOperation) => {
        setRepatriationRow(row);
        setRepatriationForm({
            direction: 'inbound',
            payment_id: row.payment_id,
            repatriation_status: (row.repatriation_status as RepatriationFormState['repatriation_status']) || 'pending',
            repatriated_amount_mzn: row.repatriated_amount_mzn > 0 ? String(row.repatriated_amount_mzn) : '',
            fx_compliance_reference: row.fx_compliance_reference || row.reference,
            export_reference: row.export_reference || row.reference,
            intermediary_bank: row.intermediary_bank || '',
            receipt_origin_country: row.counterparty_country || '',
        });
    };

    const closeRepatriationDialog = () => {
        setRepatriationRow(null);
        setRepatriationForm(emptyRepatriationForm());
    };

    const submitRepatriation = async () => {
        if (!repatriationRow || repatriationForm.payment_id === null) {
            return;
        }

        setRepatriationSaving(true);
        try {
            const response = await axios.post(route('account.reports.mozambique-exchange-control-report.repatriation'), {
                payment_id: repatriationForm.payment_id,
                repatriation_status: repatriationForm.repatriation_status,
                repatriated_amount_mzn: repatriationForm.repatriated_amount_mzn === '' ? null : Number(repatriationForm.repatriated_amount_mzn),
                fx_compliance_reference: repatriationForm.fx_compliance_reference,
                export_reference: repatriationForm.export_reference || null,
                intermediary_bank: repatriationForm.intermediary_bank || null,
                receipt_origin_country: repatriationForm.receipt_origin_country || null,
            });

            toast.success(response.data?.message || t('Repatriation status updated successfully.'));
            closeRepatriationDialog();
            await fetchData();
        } catch (error: any) {
            toast.error(error?.response?.data?.message || t('Unable to update repatriation status.'));
        } finally {
            setRepatriationSaving(false);
        }
    };

    const openDossierDialog = (row: ExchangeOperation) => {
        setDossierRow(row);
        setDossierForm({
            direction: row.direction,
            payment_id: row.payment_id,
            contract_reference: '',
            invoice_reference: row.reference,
            transport_document_reference: '',
            customs_declaration_reference: '',
            bank_settlement_reference: '',
            withholding_receipt_reference: '',
            fx_authorization_reference: '',
            correspondence_reference: '',
            notes: '',
        });
    };

    const closeDossierDialog = () => {
        setDossierRow(null);
        setDossierForm(emptyDossierForm());
    };

    const submitDossier = async () => {
        if (!dossierRow || dossierForm.payment_id === null) {
            return;
        }

        setDossierSaving(true);
        try {
            const response = await axios.post(route('account.reports.mozambique-exchange-control-report.dossier'), {
                direction: dossierForm.direction,
                payment_id: dossierForm.payment_id,
                contract_reference: dossierForm.contract_reference || null,
                invoice_reference: dossierForm.invoice_reference || null,
                transport_document_reference: dossierForm.transport_document_reference || null,
                customs_declaration_reference: dossierForm.customs_declaration_reference || null,
                bank_settlement_reference: dossierForm.bank_settlement_reference || null,
                withholding_receipt_reference: dossierForm.withholding_receipt_reference || null,
                fx_authorization_reference: dossierForm.fx_authorization_reference || null,
                correspondence_reference: dossierForm.correspondence_reference || null,
                notes: dossierForm.notes || null,
            });

            toast.success(response.data?.message || t('Exchange-control dossier saved successfully.'));
            closeDossierDialog();
            await fetchData();
        } catch (error: any) {
            toast.error(error?.response?.data?.message || t('Unable to save exchange-control dossier.'));
        } finally {
            setDossierSaving(false);
        }
    };

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <ArrowLeftRight className="h-5 w-5 text-primary" />
                    {t('Exchange Control Report')}
                </CardTitle>
                <CardDescription>
                    {t('Tracks international payments, FX documentation, dossiers and export repatriation status.')}
                </CardDescription>
            </CardHeader>

            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} className="gap-2">
                            <RefreshCw className="h-4 w-4" />
                            {loading ? t('Loading...') : t('Refresh')}
                        </Button>
                        <Button variant="outline" onClick={handleExport} className="gap-2">
                            <Download className="h-4 w-4" />
                            {t('Export CSV')}
                        </Button>
                    </div>
                </div>

                {data && (
                    <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-6">
                        {summaryCards.map((card) => (
                            <div key={card.label} className="rounded-lg border bg-white p-3">
                                <div className="text-xs uppercase text-muted-foreground">{card.label}</div>
                                <div className="mt-1 text-xl font-semibold">{card.value}</div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            <CardContent className="p-6">
                {data ? (
                    <div className="space-y-6">
                        <div className="rounded-lg border bg-slate-50 p-4 text-sm">
                            <div className="font-medium">{t('Period')}</div>
                            <div>{formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}</div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Outbound Operations')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[32rem] p-0">
                                    <table className="w-full text-sm">
                                        <thead>
                                        <tr className="border-b bg-gray-50">
                                            <th className="px-4 py-3 text-left">{t('Reference')}</th>
                                            <th className="px-4 py-3 text-left">{t('Party')}</th>
                                            <th className="px-4 py-3 text-left">{t('Currency')}</th>
                                            <th className="px-4 py-3 text-left">{t('Amount')}</th>
                                            <th className="px-4 py-3 text-left">{t('FX')}</th>
                                            <th className="px-4 py-3 text-left">{t('Actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.outbound_payments.map((row) => (
                                            <tr key={row.payment_id} className="border-b align-top">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.reference}</div>
                                                        <div className="text-xs text-muted-foreground">{formatDate(row.date)}</div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div>{row.counterparty}</div>
                                                        <div className="text-xs text-muted-foreground">{row.counterparty_country || '-'}</div>
                                                    </td>
                                                    <td className="px-4 py-3">{row.currency_code}</td>
                                                    <td className="px-4 py-3">{formatCurrency(row.amount_mzn)}</td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Badge className={badgeClass(row.domestic_fx_violation)}>{row.domestic_fx_violation ? t('Violation') : t('OK')}</Badge>
                                                            <Badge className={badgeClass(row.missing_fx_documentation)}>{row.missing_fx_documentation ? t('Missing docs') : t('Documented')}</Badge>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Button size="sm" variant="outline" onClick={() => openDossierDialog(row)}>
                                                            {t('Edit dossier')}
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.outbound_payments.length === 0 && (
                                                <tr>
                                                    <td colSpan={6} className="py-8 text-center text-muted-foreground">
                                                        {t('No outbound operations')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Inbound Export Receipts')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[32rem] p-0">
                                    <table className="w-full text-sm">
                                        <thead>
                                        <tr className="border-b bg-gray-50">
                                            <th className="px-4 py-3 text-left">{t('Reference')}</th>
                                            <th className="px-4 py-3 text-left">{t('Repatriation')}</th>
                                            <th className="px-4 py-3 text-left">{t('Amount')}</th>
                                            <th className="px-4 py-3 text-left">{t('Docs')}</th>
                                            <th className="px-4 py-3 text-left">{t('Actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.inbound_receipts.map((row) => (
                                            <tr key={row.payment_id} className="border-b align-top">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.counterparty}</div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.repatriation_status}</div>
                                                        <div className="text-xs text-muted-foreground">{row.is_export_receipt ? t('Export receipt') : t('Foreign receipt')}</div>
                                                    </td>
                                                    <td className="px-4 py-3">{formatCurrency(row.amount_mzn)}</td>
                                                    <td className="px-4 py-3">
                                                        <Badge className={badgeClass(row.missing_fx_documentation)}>{row.missing_fx_documentation ? t('Missing docs') : t('Complete')}</Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Button size="sm" variant="outline" onClick={() => openRepatriationDialog(row)}>
                                                                {t('Update repatriation')}
                                                            </Button>
                                                            <Button size="sm" variant="outline" onClick={() => openDossierDialog(row)}>
                                                                {t('Edit dossier')}
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.inbound_receipts.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="py-8 text-center text-muted-foreground">
                                                        {t('No inbound receipts')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Missing Dossiers')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.missing_dossiers.map((row) => (
                                                <tr key={`${row.payment_type}-${row.payment_id}`} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.payment_type}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Badge className="bg-red-100 text-red-800">{(row.dossier_missing_documents || []).length} {t('missing')}</Badge>
                                                            <Button size="sm" variant="outline" onClick={() => openDossierDialog(row)}>
                                                                {t('Edit')}
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.missing_dossiers.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No missing dossiers')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Pending Repatriation')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.pending_repatriation.map((row) => (
                                                <tr key={row.payment_id} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.repatriation_status}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">{formatCurrency(row.repatriated_amount_mzn)}</td>
                                                </tr>
                                            ))}
                                            {data.pending_repatriation.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No pending repatriation')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Completed Repatriation')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.completed_repatriation.map((row) => (
                                                <tr key={row.payment_id} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.repatriation_status}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">{formatCurrency(row.repatriated_amount_mzn)}</td>
                                                </tr>
                                            ))}
                                            {data.completed_repatriation.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No completed repatriation')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={FileSearch}
                        title={t('Exchange Control Report')}
                        description={t('Select a period to generate the report.')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>

            <Dialog open={Boolean(repatriationRow)} onOpenChange={(open) => !open && closeRepatriationDialog()}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{t('Update Repatriation')}</DialogTitle>
                        <DialogDescription>
                            {repatriationRow ? `${repatriationRow.reference} · ${repatriationRow.counterparty}` : ''}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('Repatriation status')}</Label>
                            <Select
                                value={repatriationForm.repatriation_status}
                                onValueChange={(value) => setRepatriationForm((current) => ({
                                    ...current,
                                    repatriation_status: value as RepatriationFormState['repatriation_status'],
                                }))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">{t('Pending')}</SelectItem>
                                    <SelectItem value="partial">{t('Partial')}</SelectItem>
                                    <SelectItem value="completed">{t('Completed')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Repatriated amount (MZN)')}</Label>
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={repatriationForm.repatriated_amount_mzn}
                                onChange={(event) => setRepatriationForm((current) => ({
                                    ...current,
                                    repatriated_amount_mzn: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('FX compliance reference')}</Label>
                            <Input
                                value={repatriationForm.fx_compliance_reference}
                                onChange={(event) => setRepatriationForm((current) => ({
                                    ...current,
                                    fx_compliance_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Export reference')}</Label>
                            <Input
                                value={repatriationForm.export_reference}
                                onChange={(event) => setRepatriationForm((current) => ({
                                    ...current,
                                    export_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Intermediary bank')}</Label>
                            <Input
                                value={repatriationForm.intermediary_bank}
                                onChange={(event) => setRepatriationForm((current) => ({
                                    ...current,
                                    intermediary_bank: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Receipt origin country')}</Label>
                            <Input
                                value={repatriationForm.receipt_origin_country}
                                onChange={(event) => setRepatriationForm((current) => ({
                                    ...current,
                                    receipt_origin_country: event.target.value,
                                }))}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeRepatriationDialog}>
                            {t('Cancel')}
                        </Button>
                        <Button type="button" onClick={submitRepatriation} disabled={repatriationSaving}>
                            {repatriationSaving ? t('Saving...') : t('Save repatriation')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={Boolean(dossierRow)} onOpenChange={(open) => !open && closeDossierDialog()}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{t('Edit Exchange-Control Dossier')}</DialogTitle>
                        <DialogDescription>
                            {dossierRow ? `${dossierRow.reference} · ${dossierRow.counterparty}` : ''}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('Contract reference')}</Label>
                            <Input
                                value={dossierForm.contract_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    contract_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Invoice reference')}</Label>
                            <Input
                                value={dossierForm.invoice_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    invoice_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Transport document')}</Label>
                            <Input
                                value={dossierForm.transport_document_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    transport_document_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Customs declaration')}</Label>
                            <Input
                                value={dossierForm.customs_declaration_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    customs_declaration_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Bank settlement reference')}</Label>
                            <Input
                                value={dossierForm.bank_settlement_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    bank_settlement_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Withholding receipt reference')}</Label>
                            <Input
                                value={dossierForm.withholding_receipt_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    withholding_receipt_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('FX authorization reference')}</Label>
                            <Input
                                value={dossierForm.fx_authorization_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    fx_authorization_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Correspondence reference')}</Label>
                            <Input
                                value={dossierForm.correspondence_reference}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    correspondence_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="md:col-span-2 space-y-2">
                            <Label>{t('Notes')}</Label>
                            <Textarea
                                rows={4}
                                value={dossierForm.notes}
                                onChange={(event) => setDossierForm((current) => ({
                                    ...current,
                                    notes: event.target.value,
                                }))}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeDossierDialog}>
                            {t('Cancel')}
                        </Button>
                        <Button type="button" onClick={submitDossier} disabled={dossierSaving}>
                            {dossierSaving ? t('Saving...') : t('Save dossier')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
