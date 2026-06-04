import { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/ui/input-error';
import { Trash2 } from 'lucide-react';
import { CreateCustomerPaymentFormData, CreateCustomerPaymentProps, SalesInvoice, CreditNote } from './types';
import { formatCurrency } from '@/utils/helpers';

export default function Create({ customers, bankAccounts, onSuccess }: CreateCustomerPaymentProps) {
    const { t } = useTranslation();
    const { mozambiqueCompliance } = usePage<any>().props as {
        mozambiqueCompliance?: {
            gifim?: {
                cash_threshold_mzn?: number;
                electronic_threshold_mzn?: number;
                electronic_payment_methods?: string[];
            };
        };
    };
    const [outstandingInvoices, setOutstandingInvoices] = useState<SalesInvoice[]>([]);
    const [availableCreditNotes, setAvailableCreditNotes] = useState<CreditNote[]>([]);
    const [selectedAllocations, setSelectedAllocations] = useState<{invoice_id: number; amount: number}[]>([]);
    const [selectedCreditNotes, setSelectedCreditNotes] = useState<{credit_note_id: number; amount: number}[]>([]);

    const paymentMethods = [
        { value: 'bank_transfer', label: t('Bank Transfer') },
        { value: 'cash', label: t('Cash') },
        { value: 'cheque', label: t('Cheque') },
        { value: 'card', label: t('Card') },
        { value: 'mobile_money', label: t('Mobile Money') },
        { value: 'other', label: t('Other') }
    ] as const;

    const mobileMoneyProviders = [
        { value: 'mpesa', label: 'M-Pesa' },
        { value: 'emola', label: 'e-Mola' },
        { value: 'mkesh', label: 'mKesh' }
    ] as const;

    const currencyOptions = [
        { value: 'MZN', label: 'MZN' },
        { value: 'USD', label: 'USD' },
        { value: 'EUR', label: 'EUR' },
        { value: 'ZAR', label: 'ZAR' },
    ] as const;

    const gifimConfig = mozambiqueCompliance?.gifim ?? {
        cash_threshold_mzn: 250000,
        electronic_threshold_mzn: 750000,
        electronic_payment_methods: ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other'],
    };

    const { data, setData, post, processing, errors } = useForm<CreateCustomerPaymentFormData>({
        payment_date: new Date().toISOString().split('T')[0],
        customer_id: '',
        bank_account_id: '',
        payment_method: 'bank_transfer',
        mobile_money_provider: '',
        mobile_money_number: '',
        reference_number: '',
        payment_amount: '',
        currency_code: 'MZN',
        exchange_rate: '1',
        foreign_amount: '',
        is_export_receipt: false,
        receipt_origin_country: '',
        export_reference: '',
        intermediary_bank: '',
        repatriation_status: 'not_applicable',
        repatriated_amount_mzn: '',
        fx_compliance_reference: '',
        gifim_alert_status: 'not_required',
        gifim_reference: '',
        gifim_reported_at: '',
        gifim_submitted_document: '',
        gifim_justification: '',
        high_value_approval_reference: '',
        notes: '',
        allocations: [],
        credit_notes: []
    });

    const bankAccountLabel = (account: any) => {
        const branchName = account.branch?.branch_name || account.branch_name;

        return branchName
            ? `${account.account_name} (${account.account_number}) - ${branchName}`
            : `${account.account_name} (${account.account_number})`;
    };

    // Update form data when selections change
    useEffect(() => {
        setData('allocations', selectedAllocations);
    }, [selectedAllocations]);

    useEffect(() => {
        setData('credit_notes', selectedCreditNotes);
    }, [selectedCreditNotes]);

    useEffect(() => {
        if (data.payment_method !== 'mobile_money') {
            setData('mobile_money_provider', '');
            setData('mobile_money_number', '');
        }
    }, [data.payment_method]);

    useEffect(() => {
        if (data.currency_code === 'MZN') {
            if (data.exchange_rate !== '1') {
                setData('exchange_rate', '1');
            }

            if (data.foreign_amount !== data.payment_amount) {
                setData('foreign_amount', data.payment_amount);
            }
        }
    }, [data.currency_code, data.payment_amount]);

    const fetchOutstandingInvoices = async (customerId: string) => {
        if (!customerId) {
            setOutstandingInvoices([]);
            setAvailableCreditNotes([]);
            return;
        }

        try {
            const response = await fetch(route('account.customer-payments.outstanding-invoices', customerId));
            const result = await response.json();
            setOutstandingInvoices(result.invoices || result || []);
            setAvailableCreditNotes(result.creditNotes || []);
        } catch (error) {
            console.error('Failed to fetch outstanding invoices:', error);
            setOutstandingInvoices([]);
            setAvailableCreditNotes([]);
        }
    };

    useEffect(() => {
        if (data.customer_id) {
            fetchOutstandingInvoices(data.customer_id);
        } else {
            setOutstandingInvoices([]);
            setAvailableCreditNotes([]);
        }
        // Clear selections when customer changes
        setSelectedAllocations([]);
        setSelectedCreditNotes([]);
        setData('payment_amount', '');
    }, [data.customer_id]);

    const addAllocation = (invoice: SalesInvoice) => {
        const existing = selectedAllocations.find(a => a.invoice_id === invoice.id);
        if (existing) return;

        const newAllocation = {
            invoice_id: invoice.id,
            amount: invoice.balance_amount
        };

        const newAllocations = [...selectedAllocations, newAllocation];
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedCreditNotes);
    };

    const removeAllocation = (invoiceId: number) => {
        const newAllocations = selectedAllocations.filter(a => a.invoice_id !== invoiceId);
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedCreditNotes);
    };

    const updateAllocationAmount = (invoiceId: number, amount: number) => {
        const newAllocations = selectedAllocations.map(a =>
            a.invoice_id === invoiceId ? { ...a, amount: Number(amount || 0) } : a
        );
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedCreditNotes);
    };

    const updateTotalAmount = (allocations: {invoice_id: number; amount: number}[], creditNotes = selectedCreditNotes) => {
        const allocationsTotal = allocations.reduce((sum, allocation) => sum + Number(allocation.amount || 0), 0);
        const creditNotesTotal = creditNotes.reduce((sum, creditNote) => sum + Number(creditNote.amount || 0), 0);
        const total = allocationsTotal - creditNotesTotal; // Credit notes reduce payment amount
        setData('payment_amount', Number(Math.max(0, total)).toFixed(2));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('account.customer-payments.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    const getInvoiceById = (id: number) => outstandingInvoices.find(inv => inv.id === id);
    const selectedCustomer = customers.find((customer) => customer.id.toString() === data.customer_id);
    const isForeignCurrency = data.currency_code !== 'MZN';
    const isNonResidentCustomer = selectedCustomer?.fiscal_residency_status === 'non_resident';
    const requiresExportReceiptForFx = isForeignCurrency && !isNonResidentCustomer;
    const isExportReceipt = requiresExportReceiptForFx || data.is_export_receipt;
    const paymentAmountValue = Number(data.payment_amount || 0);
    const exchangeRateValue = Number(data.exchange_rate || 0);
    const foreignAmountValue = Number(data.foreign_amount || 0);
    const convertedAmountMzn = isForeignCurrency && exchangeRateValue > 0
        ? foreignAmountValue * exchangeRateValue
        : paymentAmountValue;
    const fxDifferenceAmount = paymentAmountValue - convertedAmountMzn;
    const repatriatedAmountValue = Number(data.repatriated_amount_mzn || 0);
    const normalizedPaymentMethod = (data.payment_method || '').toLowerCase();
    const gifimThresholdCategory = normalizedPaymentMethod === 'cash' && paymentAmountValue >= Number(gifimConfig.cash_threshold_mzn ?? 250000)
        ? 'cash_threshold'
        : ((gifimConfig.electronic_payment_methods ?? ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other']).includes(normalizedPaymentMethod)
            && paymentAmountValue >= Number(gifimConfig.electronic_threshold_mzn ?? 750000)
            ? 'electronic_threshold'
            : null);
    const gifimAlertRequired = Boolean(gifimThresholdCategory);

    useEffect(() => {
        if (requiresExportReceiptForFx && !data.is_export_receipt) {
            setData('is_export_receipt', true);
        }
    }, [requiresExportReceiptForFx]);

    useEffect(() => {
        if (!selectedCustomer?.fiscal_country) {
            return;
        }

        if (!data.receipt_origin_country) {
            setData('receipt_origin_country', selectedCustomer.fiscal_country);
        }
    }, [selectedCustomer?.id]);

    useEffect(() => {
        if (!isExportReceipt && data.repatriation_status !== 'not_applicable') {
            setData('repatriation_status', 'not_applicable');
        }
    }, [isExportReceipt, data.repatriation_status]);

    useEffect(() => {
        if (gifimAlertRequired && data.gifim_alert_status === 'not_required') {
            setData('gifim_alert_status', 'pending');
        }

        if (!gifimAlertRequired && data.gifim_alert_status !== 'not_required') {
            setData('gifim_alert_status', 'not_required');
        }
    }, [gifimAlertRequired, data.gifim_alert_status]);

    return (
        <DialogContent className="max-w-4xl">
            <DialogHeader>
                <DialogTitle>{t('Create Customer Payment')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="payment_date" required>{t('Payment Date')}</Label>
                        <DatePicker
                            id="payment_date"
                            value={data.payment_date}
                            onChange={(value) => {
                                const formattedDate = value instanceof Date ? value.toISOString().split('T')[0] : value;
                                setData('payment_date', formattedDate);
                            }}
                            placeholder={t('Select payment date')}
                            required
                        />
                        <InputError message={errors.payment_date} />
                    </div>

                    <div>
                        <Label htmlFor="customer_id" required>{t('Customer')}</Label>
                        <Select value={data.customer_id} onValueChange={(value) => {
                            setData('customer_id', value);
                        }}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Customer')} />
                            </SelectTrigger>
                            <SelectContent>
                                {customers?.map((customer) => (
                                    <SelectItem key={customer.id} value={customer.id.toString()}>
                                        {customer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.customer_id} />
                    </div>

                    <div>
                        <Label htmlFor="bank_account_id" required>{t('Bank Account')}</Label>
                        <Select value={data.bank_account_id} onValueChange={(value) => setData('bank_account_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Bank Account')} />
                            </SelectTrigger>
                            <SelectContent>
                                {bankAccounts?.map((account) => (
                                    <SelectItem key={account.id} value={account.id.toString()}>
                                        {bankAccountLabel(account)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.bank_account_id} />
                    </div>

                    <div>
                        <Label htmlFor="reference_number">{t('Reference Number')}</Label>
                        <Input
                            id="reference_number"
                            value={data.reference_number}
                            onChange={(e) => setData('reference_number', e.target.value)}
                            placeholder={t('Check number, etc.')}
                        />
                        <InputError message={errors.reference_number} />
                    </div>

                    <div>
                        <Label htmlFor="payment_method" required>{t('Payment Method')}</Label>
                        <Select value={data.payment_method} onValueChange={(value) => setData('payment_method', value as CreateCustomerPaymentFormData['payment_method'])}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Payment Method')} />
                            </SelectTrigger>
                            <SelectContent>
                                {paymentMethods.map((method) => (
                                    <SelectItem key={method.value} value={method.value}>
                                        {method.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.payment_method} />
                    </div>

                    <div>
                        <Label htmlFor="currency_code" required>{t('Currency')}</Label>
                        <Select value={data.currency_code} onValueChange={(value) => setData('currency_code', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Currency')} />
                            </SelectTrigger>
                            <SelectContent>
                                {currencyOptions.map((currency) => (
                                    <SelectItem key={currency.value} value={currency.value}>
                                        {currency.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.currency_code} />
                    </div>

                    {isForeignCurrency && (
                        <>
                            <div>
                                <Label htmlFor="exchange_rate" required>{t('Exchange Rate')}</Label>
                                <Input
                                    id="exchange_rate"
                                    type="number"
                                    step="0.000001"
                                    min="0.000001"
                                    value={data.exchange_rate}
                                    onChange={(e) => setData('exchange_rate', e.target.value)}
                                    placeholder="63.500000"
                                />
                                <InputError message={errors.exchange_rate} />
                            </div>

                            <div>
                                <Label htmlFor="foreign_amount" required>{t('Foreign Amount')}</Label>
                                <Input
                                    id="foreign_amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={data.foreign_amount}
                                    onChange={(e) => setData('foreign_amount', e.target.value)}
                                    placeholder="100.00"
                                />
                                <InputError message={errors.foreign_amount} />
                            </div>
                        </>
                    )}

                    {data.payment_method === 'mobile_money' && (
                        <>
                            <div>
                                <Label htmlFor="mobile_money_provider" required>{t('Mobile Money Provider')}</Label>
                                <Select value={data.mobile_money_provider} onValueChange={(value) => setData('mobile_money_provider', value as CreateCustomerPaymentFormData['mobile_money_provider'])}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select Provider')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {mobileMoneyProviders.map((provider) => (
                                            <SelectItem key={provider.value} value={provider.value}>
                                                {provider.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.mobile_money_provider} />
                            </div>

                            <div>
                                <Label htmlFor="mobile_money_number" required>{t('Mobile Money Number')}</Label>
                                <Input
                                    id="mobile_money_number"
                                    value={data.mobile_money_number}
                                    onChange={(e) => setData('mobile_money_number', e.target.value)}
                                    placeholder={t('Ex: 84xxxxxxx')}
                                />
                                <InputError message={errors.mobile_money_number} />
                            </div>
                        </>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label>{t('Amount in MZN')}</Label>
                        <Input value={paymentAmountValue.toFixed(2)} readOnly />
                    </div>
                    {isForeignCurrency && (
                        <div>
                            <Label>{t('Exchange Difference (MZN)')}</Label>
                            <Input value={fxDifferenceAmount.toFixed(2)} readOnly />
                        </div>
                    )}
                </div>

                {(gifimAlertRequired || data.gifim_alert_status === 'communicated') && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-md border p-4">
                        <div>
                            <Label>{t('GIFiM Threshold Category')}</Label>
                            <Input
                                value={gifimThresholdCategory === 'cash_threshold'
                                    ? t('Cash >= 250,000 MZN')
                                    : t('Electronic >= 750,000 MZN')}
                                readOnly
                            />
                        </div>

                        <div>
                            <Label htmlFor="gifim_alert_status" required>{t('GIFiM Communication Status')}</Label>
                            <Select
                                value={data.gifim_alert_status}
                                onValueChange={(value) => setData('gifim_alert_status', value as CreateCustomerPaymentFormData['gifim_alert_status'])}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">{t('Pending')}</SelectItem>
                                    <SelectItem value="communicated">{t('Communicated')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gifim_alert_status} />
                        </div>

                        <div>
                            <Label htmlFor="high_value_approval_reference" required>{t('High-Value Approval Reference')}</Label>
                            <Input
                                id="high_value_approval_reference"
                                value={data.high_value_approval_reference}
                                onChange={(e) => setData('high_value_approval_reference', e.target.value)}
                                placeholder={t('Ex: FIN-APP-HV-2026-001')}
                            />
                            <InputError message={errors.high_value_approval_reference} />
                        </div>

                        {data.gifim_alert_status === 'communicated' && (
                            <>
                                <div>
                                    <Label htmlFor="gifim_reference" required>{t('GIFiM Reference')}</Label>
                                    <Input
                                        id="gifim_reference"
                                        value={data.gifim_reference}
                                        onChange={(e) => setData('gifim_reference', e.target.value)}
                                        placeholder={t('Ex: GIFIM-2026-001')}
                                    />
                                    <InputError message={errors.gifim_reference} />
                                </div>

                                <div>
                                    <Label htmlFor="gifim_submitted_document" required>{t('Submitted Document')}</Label>
                                    <Input
                                        id="gifim_submitted_document"
                                        value={data.gifim_submitted_document}
                                        onChange={(e) => setData('gifim_submitted_document', e.target.value)}
                                        placeholder={t('Ex: comprovativo.pdf')}
                                    />
                                    <InputError message={errors.gifim_submitted_document} />
                                </div>

                                <div>
                                    <Label htmlFor="gifim_reported_at">{t('Communication Date')}</Label>
                                    <Input
                                        id="gifim_reported_at"
                                        type="date"
                                        value={data.gifim_reported_at}
                                        onChange={(e) => setData('gifim_reported_at', e.target.value)}
                                    />
                                    <InputError message={errors.gifim_reported_at} />
                                </div>

                                <div>
                                    <Label htmlFor="gifim_justification">{t('GIFiM Justification')}</Label>
                                    <Input
                                        id="gifim_justification"
                                        value={data.gifim_justification}
                                        onChange={(e) => setData('gifim_justification', e.target.value)}
                                        placeholder={t('Optional notes')}
                                    />
                                    <InputError message={errors.gifim_justification} />
                                </div>
                            </>
                        )}
                    </div>
                )}

                <div className="flex items-start gap-2 rounded-md border p-3">
                    <Checkbox
                        id="is_export_receipt"
                        checked={isExportReceipt}
                        disabled={requiresExportReceiptForFx}
                        onCheckedChange={(checked) => setData('is_export_receipt', !!checked)}
                    />
                    <div className="space-y-1">
                        <Label htmlFor="is_export_receipt">{t('Export revenue receipt')}</Label>
                        <p className="text-xs text-gray-500">
                            {requiresExportReceiptForFx
                                ? t('Foreign-currency domestic receipts must be flagged as export-related for FX compliance tracking.')
                                : t('Enable for receipts linked to export revenue and repatriation control.')}
                        </p>
                    </div>
                </div>

                {isExportReceipt && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-md border p-4">
                        <div>
                            <Label htmlFor="receipt_origin_country" required>{t('Receipt Origin Country')}</Label>
                            <Input
                                id="receipt_origin_country"
                                value={data.receipt_origin_country}
                                onChange={(e) => setData('receipt_origin_country', e.target.value)}
                                placeholder={t('Ex: South Africa')}
                            />
                            <InputError message={errors.receipt_origin_country} />
                        </div>

                        <div>
                            <Label htmlFor="export_reference" required>{t('Export Reference')}</Label>
                            <Input
                                id="export_reference"
                                value={data.export_reference}
                                onChange={(e) => setData('export_reference', e.target.value)}
                                placeholder={t('Invoice/DU reference')}
                            />
                            <InputError message={errors.export_reference} />
                        </div>

                        <div>
                            <Label htmlFor="intermediary_bank" required>{t('Intermediary Bank')}</Label>
                            <Input
                                id="intermediary_bank"
                                value={data.intermediary_bank}
                                onChange={(e) => setData('intermediary_bank', e.target.value)}
                                placeholder={t('Bank receiving foreign funds')}
                            />
                            <InputError message={errors.intermediary_bank} />
                        </div>

                        <div>
                            <Label htmlFor="fx_compliance_reference" required>{t('FX Compliance Reference')}</Label>
                            <Input
                                id="fx_compliance_reference"
                                value={data.fx_compliance_reference}
                                onChange={(e) => setData('fx_compliance_reference', e.target.value)}
                                placeholder={t('BM/Bank compliance reference')}
                            />
                            <InputError message={errors.fx_compliance_reference} />
                        </div>

                        <div>
                            <Label htmlFor="repatriation_status">{t('Repatriation Status')}</Label>
                            <Select
                                value={data.repatriation_status}
                                onValueChange={(value) => setData('repatriation_status', value as CreateCustomerPaymentFormData['repatriation_status'])}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">{t('Pending')}</SelectItem>
                                    <SelectItem value="partial">{t('Partial')}</SelectItem>
                                    <SelectItem value="completed">{t('Completed')}</SelectItem>
                                    <SelectItem value="not_applicable">{t('Not applicable')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.repatriation_status} />
                        </div>

                        <div>
                            <Label htmlFor="repatriated_amount_mzn">{t('Repatriated Amount (MZN)')}</Label>
                            <Input
                                id="repatriated_amount_mzn"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.repatriated_amount_mzn}
                                onChange={(e) => setData('repatriated_amount_mzn', e.target.value)}
                                placeholder={paymentAmountValue.toFixed(2)}
                            />
                            <InputError message={errors.repatriated_amount_mzn} />
                            {data.repatriation_status === 'completed' && (
                                <p className="text-xs text-gray-500 mt-1">
                                    {t('Completed repatriation should cover at least')}: {paymentAmountValue.toFixed(2)} MZN.
                                </p>
                            )}
                            {repatriatedAmountValue > 0 && (
                                <p className="text-xs text-gray-500 mt-1">
                                    {t('Registered')}: {repatriatedAmountValue.toFixed(2)} MZN
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {data.customer_id && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">{t('Outstanding Invoices')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {outstandingInvoices.length > 0 ? (
                                    <div className="space-y-2 max-h-40 overflow-y-auto">
                                        {outstandingInvoices.map((invoice) => (
                                            <div key={invoice.id} className="flex items-center justify-between p-2 border rounded">
                                                <div>
                                                    <span className="font-medium">{invoice.invoice_number}</span>
                                                    {invoice.counterparty_name && (
                                                        <div className="text-xs text-gray-500">{invoice.counterparty_name}</div>
                                                    )}
                                                    {invoice.counterparty_tax_number && (
                                                        <div className="text-xs text-gray-500">
                                                            {invoice.counterparty_tax_label || t('Tax Number')}: {invoice.counterparty_tax_number}
                                                        </div>
                                                    )}
                                                    <span className="text-sm text-gray-500 ml-2">
                                                        Balance: {formatCurrency(invoice.balance_amount)}
                                                    </span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    onClick={() => addAllocation(invoice)}
                                                    disabled={selectedAllocations.some(a => a.invoice_id === invoice.id)}
                                                >
                                                    {t('Add')}
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-4 text-gray-500">
                                        {t('No outstanding invoices found for this customer')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">{t('Available Credit Notes')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {availableCreditNotes.length > 0 ? (
                                    <div className="space-y-2 max-h-40 overflow-y-auto">
                                        {availableCreditNotes.map((creditNote) => (
                                            <div key={creditNote.id} className="flex items-center justify-between p-2 border rounded">
                                                <div>
                                                    <span className="font-medium">{creditNote.credit_note_number}</span>
                                                    {creditNote.counterparty_name && (
                                                        <div className="text-xs text-gray-500">{creditNote.counterparty_name}</div>
                                                    )}
                                                    {creditNote.counterparty_tax_number && (
                                                        <div className="text-xs text-gray-500">
                                                            {creditNote.counterparty_tax_label || t('Tax Number')}: {creditNote.counterparty_tax_number}
                                                        </div>
                                                    )}
                                                    <span className="text-sm text-gray-500 ml-2">
                                                        Balance: {formatCurrency(creditNote.balance_amount)}
                                                    </span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const totalInvoiceAmount = selectedAllocations.reduce((sum, a) => sum + a.amount, 0);
                                                        const currentCreditNotesSum = selectedCreditNotes.reduce((sum, c) => sum + c.amount, 0);
                                                        const remainingAmount = totalInvoiceAmount - currentCreditNotesSum;
                                                        const maxAmount = Math.min(creditNote.balance_amount, remainingAmount);
                                                        const newCreditNote = {
                                                            credit_note_id: creditNote.id,
                                                            amount: maxAmount > 0 ? maxAmount : creditNote.balance_amount
                                                        };
                                                        const newCreditNotes = [...selectedCreditNotes, newCreditNote];
                                                        setSelectedCreditNotes(newCreditNotes);
                                                        updateTotalAmount(selectedAllocations, newCreditNotes);
                                                    }}
                                                    disabled={selectedCreditNotes.some(c => c.credit_note_id === creditNote.id)}
                                                >
                                                    {t('Apply')}
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-4 text-gray-500">
                                        {t('No credit notes available for this customer')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {(selectedAllocations.length > 0 || selectedCreditNotes.length > 0) && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">{t('Payment Summary')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {selectedAllocations.map((allocation) => {
                                    const invoice = getInvoiceById(allocation.invoice_id);
                                    return (
                                        <div key={allocation.invoice_id} className="flex items-center gap-3 p-3 border rounded">
                                            <div className="flex-1">
                                                <div className="font-medium">{invoice?.invoice_number}</div>
                                                {invoice?.counterparty_name && (
                                                    <div className="text-xs text-gray-500">{invoice.counterparty_name}</div>
                                                )}
                                                <div className="text-sm text-gray-500">
                                                    {t('Balance')}: {formatCurrency(invoice?.balance_amount || 0)}
                                                </div>
                                            </div>
                                            <div className="w-32">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={allocation.amount}
                                                    onChange={(e) => updateAllocationAmount(allocation.invoice_id, Number(e.target.value) || 0)}
                                                    max={invoice?.balance_amount}
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => removeAllocation(allocation.invoice_id)}
                                            >
                                                <Trash2 className="h-4 w-4 text-red-600" />
                                            </Button>
                                        </div>
                                    );
                                })}
                                {selectedCreditNotes.map((creditNote, index) => {
                                    const note = availableCreditNotes.find(c => c.id === creditNote.credit_note_id);
                                    return (
                                        <div key={`credit-${index}`} className="flex items-center gap-3 p-3 border rounded bg-green-50">
                                            <div className="flex-1">
                                                <div className="font-medium text-green-700">{note?.credit_note_number}</div>
                                                {note?.counterparty_name && (
                                                    <div className="text-xs text-gray-500">{note.counterparty_name}</div>
                                                )}
                                                <div className="text-sm text-gray-500">
                                                    {t('Credit applied to payment')}
                                                </div>
                                            </div>
                                            <div className="w-32">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={creditNote.amount}
                                                    onChange={(e) => {
                                                        const newAmount = Number(e.target.value);
                                                        if (isNaN(newAmount)) return;
                                                        const note = availableCreditNotes.find(c => c.id === creditNote.credit_note_id);
                                                        const totalInvoiceAmount = selectedAllocations.reduce((sum, a) => sum + Number(a.amount || 0), 0);
                                                        const otherCreditNotesSum = selectedCreditNotes.reduce((sum, c, i) => 
                                                            i !== index ? sum + Number(c.amount || 0) : sum, 0
                                                        );
                                                        const maxAllowedForThis = totalInvoiceAmount - otherCreditNotesSum;
                                                        const maxAmount = Math.min(note?.balance_amount || 0, maxAllowedForThis);
                                                        const validAmount = Math.max(0, Math.min(newAmount, maxAmount));
                                                        const newCreditNotes = selectedCreditNotes.map((c, i) => 
                                                            i === index ? { ...c, amount: validAmount } : c
                                                        );
                                                        setSelectedCreditNotes(newCreditNotes);
                                                        updateTotalAmount(selectedAllocations, newCreditNotes);
                                                    }}
                                                    max={Math.min(
                                                        availableCreditNotes.find(c => c.id === creditNote.credit_note_id)?.balance_amount || 0,
                                                        selectedAllocations.reduce((sum, a) => sum + a.amount, 0)
                                                    )}
                                                    className="text-right"
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    const newCreditNotes = selectedCreditNotes.filter((_, i) => i !== index);
                                                    setSelectedCreditNotes(newCreditNotes);
                                                    updateTotalAmount(selectedAllocations, newCreditNotes);
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4 text-red-600" />
                                            </Button>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div>
                    <CurrencyInput
                        label={t('Total Payment Amount (MZN)')}
                        value={data.payment_amount}
                        onChange={(value) => {
                            setData('payment_amount', value);
                            // Clear allocations if total is changed manually
                            if (parseFloat(value) !== selectedAllocations.reduce((sum, a) => sum + a.amount, 0)) {
                                setSelectedAllocations([]);
                            }
                        }}
                        error={errors.payment_amount}
                        required
                    />
                </div>

                <div>
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        rows={3}
                        placeholder={t('Enter notes')}
                    />
                    <InputError message={errors.notes} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing || (!selectedAllocations.length && !selectedCreditNotes.length)}
                    >
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
