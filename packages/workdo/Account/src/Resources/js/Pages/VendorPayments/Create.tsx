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
import { CreateVendorPaymentFormData, CreateVendorPaymentProps, PurchaseInvoice, DebitNote } from './types';
import { formatCurrency } from '@/utils/helpers';

export default function Create({ vendors, bankAccounts, onSuccess }: CreateVendorPaymentProps) {
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
    const [outstandingInvoices, setOutstandingInvoices] = useState<PurchaseInvoice[]>([]);
    const [availableDebitNotes, setAvailableDebitNotes] = useState<DebitNote[]>([]);
    const [selectedAllocations, setSelectedAllocations] = useState<{invoice_id: number; amount: number}[]>([]);
    const [selectedDebitNotes, setSelectedDebitNotes] = useState<{debit_note_id: number; amount: number}[]>([]);

    const paymentMethods = [
        { value: 'bank_transfer', label: t('Bank Transfer') },
        { value: 'cash', label: t('Cash') },
        { value: 'cheque', label: t('Cheque') },
        { value: 'card', label: t('Card') },
        { value: 'mobile_money', label: t('Mobile Money') },
        { value: 'other', label: t('Other') }
    ] as const;

    const paymentPurposeOptions = [
        { value: 'settlement', label: t('Invoice settlement') },
        { value: 'advance', label: t('Supplier advance') },
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

    const serviceTypes = [
        { value: 'consulting', label: t('Consulting') },
        { value: 'digital_services', label: t('Digital Services') },
        { value: 'licensing', label: t('Licensing / Royalties') },
        { value: 'goods_import', label: t('Goods Import') },
        { value: 'other', label: t('Other') },
    ] as const;

    const bankAccountLabel = (account: any) => {
        const branchName = account.branch?.branch_name || account.branch_name;

        return branchName
            ? `${account.account_name} (${account.account_number}) - ${branchName}`
            : `${account.account_name} (${account.account_number})`;
    };

    const { data, setData, post, processing, errors } = useForm<CreateVendorPaymentFormData>({
        payment_date: new Date().toISOString().split('T')[0],
        vendor_id: '',
        payment_purpose: 'settlement',
        bank_account_id: '',
        payment_method: 'bank_transfer',
        mobile_money_provider: '',
        mobile_money_number: '',
        reference_number: '',
        payment_amount: '',
        currency_code: 'MZN',
        exchange_rate: '1',
        foreign_amount: '',
        is_international_payment: false,
        beneficiary_country: '',
        service_type: '',
        withholding_tax_treatment: '',
        withholding_tax_rate: '',
        withholding_tax_amount: '',
        withholding_exemption_reason: '',
        adt_certificate_reference: '',
        fiscal_compliance_reference: '',
        financial_approval_reference: '',
        fx_authorization_reference: '',
        contract_reference: '',
        invoice_reference: '',
        bank_settlement_reference: '',
        withholding_receipt_reference: '',
        correspondence_reference: '',
        gifim_alert_status: 'not_required',
        gifim_reference: '',
        gifim_reported_at: '',
        gifim_submitted_document: '',
        gifim_justification: '',
        high_value_approval_reference: '',
        notes: '',
        allocations: [],
        debit_notes: []
    });

    // Update form data when selections change
    useEffect(() => {
        setData('allocations', selectedAllocations);
    }, [selectedAllocations]);

    useEffect(() => {
        setData('debit_notes', selectedDebitNotes);
    }, [selectedDebitNotes]);

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

    const fetchOutstandingInvoices = async (vendorId: string) => {
        if (!vendorId) {
            setOutstandingInvoices([]);
            setAvailableDebitNotes([]);
            return;
        }

        try {
            const response = await fetch(route('account.vendor-payments.vendors.outstanding', vendorId));
            const data = await response.json();
            setOutstandingInvoices(data.invoices || data || []);
            setAvailableDebitNotes(data.debitNotes || []);
        } catch (error) {
            console.error('Failed to fetch outstanding invoices:', error);
            setOutstandingInvoices([]);
            setAvailableDebitNotes([]);
        }
    };

    useEffect(() => {
        if (data.vendor_id && data.payment_purpose !== 'advance') {
            fetchOutstandingInvoices(data.vendor_id);
        } else {
            setOutstandingInvoices([]);
            setAvailableDebitNotes([]);
        }
        // Clear selections when vendor changes
        setSelectedAllocations([]);
        setSelectedDebitNotes([]);
        setData('payment_amount', '');
    }, [data.vendor_id, data.payment_purpose]);

    useEffect(() => {
        if (data.payment_purpose === 'advance') {
            setSelectedAllocations([]);
            setSelectedDebitNotes([]);
            setOutstandingInvoices([]);
            setAvailableDebitNotes([]);
        } else if (data.vendor_id) {
            fetchOutstandingInvoices(data.vendor_id);
        }
    }, [data.payment_purpose]);

    const addAllocation = (invoice: PurchaseInvoice) => {
        const existing = selectedAllocations.find(a => a.invoice_id === invoice.id);
        if (existing) return;

        const newAllocation = {
            invoice_id: invoice.id,
            amount: invoice.balance_amount
        };

        const newAllocations = [...selectedAllocations, newAllocation];
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedDebitNotes);
    };

    const removeAllocation = (invoiceId: number) => {
        const newAllocations = selectedAllocations.filter(a => a.invoice_id !== invoiceId);
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedDebitNotes);
    };

    const updateAllocationAmount = (invoiceId: number, amount: number) => {
        const newAllocations = selectedAllocations.map(a =>
            a.invoice_id === invoiceId ? { ...a, amount: Number(amount || 0) } : a
        );
        setSelectedAllocations(newAllocations);
        updateTotalAmount(newAllocations, selectedDebitNotes);
    };

    const updateTotalAmount = (allocations: {invoice_id: number; amount: number}[], debitNotes = selectedDebitNotes) => {
        const allocationsTotal = allocations.reduce((sum, allocation) => sum + Number(allocation.amount || 0), 0);
        const debitNotesTotal = debitNotes.reduce((sum, debitNote) => sum + Number(debitNote.amount || 0), 0);
        const total = allocationsTotal - debitNotesTotal; // Debit notes reduce payment amount
        setData('payment_amount', Number(Math.max(0, total)).toFixed(2));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();





        post(route('account.vendor-payments.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    const getInvoiceById = (id: number) => outstandingInvoices.find(inv => inv.id === id);
    const selectedVendor = vendors.find((vendor) => vendor.id.toString() === data.vendor_id);
    const isAdvancePayment = data.payment_purpose === 'advance';
    const isForeignCurrency = data.currency_code !== 'MZN';
    const isNonResidentVendor = selectedVendor?.fiscal_residency_status === 'non_resident';
    const isInternationalRequiredByContext = isForeignCurrency || isNonResidentVendor;
    const isInternationalPayment = isInternationalRequiredByContext || data.is_international_payment;
    const paymentAmountValue = Number(data.payment_amount || 0);
    const exchangeRateValue = Number(data.exchange_rate || 0);
    const foreignAmountValue = Number(data.foreign_amount || 0);
    const convertedAmountMzn = isForeignCurrency && exchangeRateValue > 0
        ? foreignAmountValue * exchangeRateValue
        : paymentAmountValue;
    const fxDifferenceAmount = paymentAmountValue - convertedAmountMzn;
    const normalizedPaymentMethod = (data.payment_method || '').toLowerCase();
    const gifimThresholdCategory = normalizedPaymentMethod === 'cash' && paymentAmountValue >= Number(gifimConfig.cash_threshold_mzn ?? 250000)
        ? 'cash_threshold'
        : ((gifimConfig.electronic_payment_methods ?? ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other']).includes(normalizedPaymentMethod)
            && paymentAmountValue >= Number(gifimConfig.electronic_threshold_mzn ?? 750000)
            ? 'electronic_threshold'
            : null);
    const gifimAlertRequired = Boolean(gifimThresholdCategory);

    useEffect(() => {
        if (isInternationalRequiredByContext && !data.is_international_payment) {
            setData('is_international_payment', true);
        }
    }, [isInternationalRequiredByContext]);

    useEffect(() => {
        if (gifimAlertRequired && data.gifim_alert_status === 'not_required') {
            setData('gifim_alert_status', 'pending');
        }

        if (!gifimAlertRequired && data.gifim_alert_status !== 'not_required') {
            setData('gifim_alert_status', 'not_required');
        }
    }, [gifimAlertRequired, data.gifim_alert_status]);

    useEffect(() => {
        if (!selectedVendor) {
            return;
        }

        if (selectedVendor.fiscal_country && !data.beneficiary_country) {
            setData('beneficiary_country', selectedVendor.fiscal_country);
        }

        if (selectedVendor.withholding_tax_applicable && !data.withholding_tax_treatment) {
            setData('withholding_tax_treatment', 'withheld');
        }
    }, [selectedVendor?.id]);

    useEffect(() => {
        if (isAdvancePayment || selectedAllocations.length !== 1 || data.invoice_reference) {
            return;
        }

        const invoice = getInvoiceById(selectedAllocations[0].invoice_id);
        if (invoice?.invoice_number) {
            setData('invoice_reference', invoice.invoice_number);
        }
    }, [selectedAllocations, outstandingInvoices, data.invoice_reference]);

    return (
        <DialogContent className="max-w-4xl">
            <DialogHeader>
                <DialogTitle>{t('Create Vendor Payment')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="payment_date" required>{t('Payment Date')}</Label>
                        <DatePicker
                            id="payment_date"
                            value={data.payment_date}
                            onChange={(value) => {
                                // Ensure date is in YYYY-MM-DD format
                                const formattedDate = value instanceof Date ? value.toISOString().split('T')[0] : value;
                                setData('payment_date', formattedDate);
                            }}
                            placeholder={t('Select payment date')}
                            required
                        />
                        <InputError message={errors.payment_date} />
                    </div>

                    <div>
                        <Label htmlFor="vendor_id" required>{t('Vendor')}</Label>
                        <Select value={data.vendor_id} onValueChange={(value) => {
                            setData('vendor_id', value);
                        }}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Vendor')} />
                            </SelectTrigger>
                            <SelectContent>
                                {vendors?.map((vendor) => (
                                    <SelectItem key={vendor.id} value={vendor.id.toString()}>
                                        {vendor.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.vendor_id} />
                    </div>

                    <div>
                        <Label htmlFor="payment_purpose" required>{t('Payment Purpose')}</Label>
                        <Select value={data.payment_purpose} onValueChange={(value) => setData('payment_purpose', value as CreateVendorPaymentFormData['payment_purpose'])}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Purpose')} />
                            </SelectTrigger>
                            <SelectContent>
                                {paymentPurposeOptions.map((purpose) => (
                                    <SelectItem key={purpose.value} value={purpose.value}>
                                        {purpose.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.payment_purpose} />
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
                        <Select value={data.payment_method} onValueChange={(value) => setData('payment_method', value as CreateVendorPaymentFormData['payment_method'])}>
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
                                <Select value={data.mobile_money_provider} onValueChange={(value) => setData('mobile_money_provider', value as CreateVendorPaymentFormData['mobile_money_provider'])}>
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

                <div className="flex items-start gap-2 rounded-md border p-3">
                    <Checkbox
                        id="is_international_payment"
                        checked={isInternationalPayment}
                        disabled={isInternationalRequiredByContext}
                        onCheckedChange={(checked) => setData('is_international_payment', !!checked)}
                    />
                    <div className="space-y-1">
                        <Label htmlFor="is_international_payment">{t('International payment / remittance')}</Label>
                        <p className="text-xs text-gray-500">
                            {isInternationalRequiredByContext
                                ? t('This payment is treated as international due to vendor residency or foreign currency.')
                                : t('Enable this option when payment requires international fiscal/currency compliance checks.')}
                        </p>
                    </div>
                </div>

                {isInternationalPayment && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-md border p-4">
                        <div>
                            <Label htmlFor="beneficiary_country" required>{t('Beneficiary Country')}</Label>
                            <Input
                                id="beneficiary_country"
                                value={data.beneficiary_country}
                                onChange={(e) => setData('beneficiary_country', e.target.value)}
                                placeholder={t('Ex: Portugal')}
                            />
                            <InputError message={errors.beneficiary_country} />
                        </div>

                        <div>
                            <Label htmlFor="service_type" required>{t('Service Type')}</Label>
                            <Select value={data.service_type} onValueChange={(value) => setData('service_type', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select service type')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {serviceTypes.map((serviceType) => (
                                        <SelectItem key={serviceType.value} value={serviceType.value}>
                                            {serviceType.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.service_type} />
                        </div>

                        <div>
                            <Label htmlFor="withholding_tax_treatment" required>{t('Withholding Tax Treatment')}</Label>
                            <Select
                                value={data.withholding_tax_treatment}
                                onValueChange={(value) => setData('withholding_tax_treatment', value as CreateVendorPaymentFormData['withholding_tax_treatment'])}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select tax treatment')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="withheld">{t('Withheld')}</SelectItem>
                                    <SelectItem value="exempt">{t('Exempt')}</SelectItem>
                                    <SelectItem value="adt_reduced">{t('ADT Reduced Rate')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.withholding_tax_treatment} />
                        </div>

                        {(data.withholding_tax_treatment === 'withheld' || data.withholding_tax_treatment === 'adt_reduced') && (
                            <>
                                <div>
                                    <Label htmlFor="withholding_tax_rate" required>{t('Withholding Tax Rate (%)')}</Label>
                                    <Input
                                        id="withholding_tax_rate"
                                        type="number"
                                        step="0.0001"
                                        min="0.0001"
                                        value={data.withholding_tax_rate}
                                        onChange={(e) => setData('withholding_tax_rate', e.target.value)}
                                        placeholder="20.0000"
                                    />
                                    <InputError message={errors.withholding_tax_rate} />
                                </div>

                                <div>
                                    <Label htmlFor="withholding_tax_amount" required>{t('Withholding Tax Amount')}</Label>
                                    <Input
                                        id="withholding_tax_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.withholding_tax_amount}
                                        onChange={(e) => setData('withholding_tax_amount', e.target.value)}
                                        placeholder="1000.00"
                                    />
                                    <InputError message={errors.withholding_tax_amount} />
                                </div>
                            </>
                        )}

                        {data.withholding_tax_treatment === 'exempt' && (
                            <div className="md:col-span-2">
                                <Label htmlFor="withholding_exemption_reason" required>{t('Withholding Exemption Legal Basis')}</Label>
                                <Input
                                    id="withholding_exemption_reason"
                                    value={data.withholding_exemption_reason}
                                    onChange={(e) => setData('withholding_exemption_reason', e.target.value)}
                                    placeholder={t('Ex: legal exemption reference')}
                                />
                                <InputError message={errors.withholding_exemption_reason} />
                            </div>
                        )}

                        {data.withholding_tax_treatment === 'adt_reduced' && (
                            <div>
                                <Label htmlFor="adt_certificate_reference" required>{t('ADT Certificate Reference')}</Label>
                                <Input
                                    id="adt_certificate_reference"
                                    value={data.adt_certificate_reference}
                                    onChange={(e) => setData('adt_certificate_reference', e.target.value)}
                                    placeholder={t('Ex: ADT-2026-0004')}
                                />
                                <InputError message={errors.adt_certificate_reference} />
                            </div>
                        )}

                        <div>
                            <Label htmlFor="fiscal_compliance_reference" required>{t('Fiscal Compliance Reference')}</Label>
                            <Input
                                id="fiscal_compliance_reference"
                                value={data.fiscal_compliance_reference}
                                onChange={(e) => setData('fiscal_compliance_reference', e.target.value)}
                                placeholder={t('Ex: WHT/IRPC proof ref')}
                            />
                            <InputError message={errors.fiscal_compliance_reference} />
                        </div>

                        <div>
                            <Label htmlFor="financial_approval_reference" required>{t('Financial Approval Reference')}</Label>
                            <Input
                                id="financial_approval_reference"
                                value={data.financial_approval_reference}
                                onChange={(e) => setData('financial_approval_reference', e.target.value)}
                                placeholder={t('Ex: FIN-APP-2026-002')}
                            />
                            <InputError message={errors.financial_approval_reference} />
                        </div>

                        <div>
                            <Label htmlFor="fx_authorization_reference" required>{t('FX Authorization Reference')}</Label>
                            <Input
                                id="fx_authorization_reference"
                                value={data.fx_authorization_reference}
                                onChange={(e) => setData('fx_authorization_reference', e.target.value)}
                                placeholder={t('Ex: BM-AUT-2026-019')}
                            />
                            <InputError message={errors.fx_authorization_reference} />
                        </div>

                        <div>
                            <Label htmlFor="contract_reference">{t('Contract Reference')}</Label>
                            <Input
                                id="contract_reference"
                                value={data.contract_reference}
                                onChange={(e) => setData('contract_reference', e.target.value)}
                                placeholder={t('Ex: CTR-2026-001')}
                            />
                            <InputError message={errors.contract_reference} />
                        </div>

                        <div>
                            <Label htmlFor="invoice_reference">{t('Invoice Reference')}</Label>
                            <Input
                                id="invoice_reference"
                                value={data.invoice_reference}
                                onChange={(e) => setData('invoice_reference', e.target.value)}
                                placeholder={t('Ex: FR-2026-001')}
                            />
                            <InputError message={errors.invoice_reference} />
                        </div>

                        <div>
                            <Label htmlFor="bank_settlement_reference">{t('Bank Settlement Reference')}</Label>
                            <Input
                                id="bank_settlement_reference"
                                value={data.bank_settlement_reference}
                                onChange={(e) => setData('bank_settlement_reference', e.target.value)}
                                placeholder={t('Ex: SWIFT/settlement reference')}
                            />
                            <InputError message={errors.bank_settlement_reference} />
                        </div>

                        <div>
                            <Label htmlFor="correspondence_reference">{t('Bank / FX Correspondence Reference')}</Label>
                            <Input
                                id="correspondence_reference"
                                value={data.correspondence_reference}
                                onChange={(e) => setData('correspondence_reference', e.target.value)}
                                placeholder={t('Ex: EMAIL-BANK-2026-001')}
                            />
                            <InputError message={errors.correspondence_reference} />
                        </div>

                        {(data.withholding_tax_treatment === 'withheld' || data.withholding_tax_treatment === 'adt_reduced') && (
                            <div className="md:col-span-2">
                                <Label htmlFor="withholding_receipt_reference">{t('Withholding Receipt Reference')}</Label>
                                <Input
                                    id="withholding_receipt_reference"
                                    value={data.withholding_receipt_reference}
                                    onChange={(e) => setData('withholding_receipt_reference', e.target.value)}
                                    placeholder={t('Ex: IRPC/WHT receipt reference')}
                                />
                                <InputError message={errors.withholding_receipt_reference} />
                            </div>
                        )}
                    </div>
                )}

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
                                onValueChange={(value) => setData('gifim_alert_status', value as CreateVendorPaymentFormData['gifim_alert_status'])}
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

                {data.vendor_id && !isAdvancePayment && (
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
                                        {t('No outstanding invoices found for this vendor')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">{t('Available Debit Notes')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {availableDebitNotes.length > 0 ? (
                                    <div className="space-y-2 max-h-40 overflow-y-auto">
                                        {availableDebitNotes.map((debitNote) => (
                                            <div key={debitNote.id} className="flex items-center justify-between p-2 border rounded">
                                                <div>
                                                    <span className="font-medium">{debitNote.debit_note_number}</span>
                                                    {debitNote.counterparty_name && (
                                                        <div className="text-xs text-gray-500">{debitNote.counterparty_name}</div>
                                                    )}
                                                    {debitNote.counterparty_tax_number && (
                                                        <div className="text-xs text-gray-500">
                                                            {debitNote.counterparty_tax_label || t('Tax Number')}: {debitNote.counterparty_tax_number}
                                                        </div>
                                                    )}
                                                    <span className="text-sm text-gray-500 ml-2">
                                                        Balance: {formatCurrency(debitNote.balance_amount)}
                                                    </span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const totalInvoiceAmount = selectedAllocations.reduce((sum, a) => sum + a.amount, 0);
                                                        const currentDebitNotesSum = selectedDebitNotes.reduce((sum, d) => sum + d.amount, 0);
                                                        const remainingAmount = totalInvoiceAmount - currentDebitNotesSum;
                                                        const maxAmount = Math.min(debitNote.balance_amount, remainingAmount);
                                                        const newDebitNote = {
                                                            debit_note_id: debitNote.id,
                                                            amount: maxAmount > 0 ? maxAmount : debitNote.balance_amount
                                                        };
                                                        const newDebitNotes = [...selectedDebitNotes, newDebitNote];
                                                        setSelectedDebitNotes(newDebitNotes);
                                                        updateTotalAmount(selectedAllocations, newDebitNotes);
                                                    }}
                                                    disabled={selectedDebitNotes.some(d => d.debit_note_id === debitNote.id)}
                                                >
                                                    {t('Apply')}
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-4 text-gray-500">
                                        {t('No debit notes available for this vendor')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {!isAdvancePayment && (selectedAllocations.length > 0 || selectedDebitNotes.length > 0) && (
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
                                {selectedDebitNotes.map((debitNote, index) => {
                                    const note = availableDebitNotes.find(d => d.id === debitNote.debit_note_id);
                                    return (
                                        <div key={`debit-${index}`} className="flex items-center gap-3 p-3 border rounded bg-green-50">
                                            <div className="flex-1">
                                                <div className="font-medium text-green-700">{note?.debit_note_number}</div>
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
                                                    value={debitNote.amount}
                                                    onChange={(e) => {
                                                        const newAmount = Number(e.target.value);
                                                        if (isNaN(newAmount)) return;
                                                        const note = availableDebitNotes.find(d => d.id === debitNote.debit_note_id);
                                                        const totalInvoiceAmount = selectedAllocations.reduce((sum, a) => sum + Number(a.amount || 0), 0);
                                                        const otherDebitNotesSum = selectedDebitNotes.reduce((sum, d, i) => 
                                                            i !== index ? sum + Number(d.amount || 0) : sum, 0
                                                        );
                                                        const maxAllowedForThis = totalInvoiceAmount - otherDebitNotesSum;
                                                        const maxAmount = Math.min(note?.balance_amount || 0, maxAllowedForThis);
                                                        const validAmount = Math.max(0, Math.min(newAmount, maxAmount));
                                                        const newDebitNotes = selectedDebitNotes.map((d, i) => 
                                                            i === index ? { ...d, amount: validAmount } : d
                                                        );
                                                        setSelectedDebitNotes(newDebitNotes);
                                                        updateTotalAmount(selectedAllocations, newDebitNotes);
                                                    }}
                                                    max={Math.min(
                                                        availableDebitNotes.find(d => d.id === debitNote.debit_note_id)?.balance_amount || 0,
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
                                                    const newDebitNotes = selectedDebitNotes.filter((_, i) => i !== index);
                                                    setSelectedDebitNotes(newDebitNotes);
                                                    updateTotalAmount(selectedAllocations, newDebitNotes);
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
                            if (!isAdvancePayment && parseFloat(value) !== selectedAllocations.reduce((sum, a) => sum + a.amount, 0)) {
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
                        disabled={processing || (isAdvancePayment ? Number(data.payment_amount || 0) <= 0 : (!selectedAllocations.length && !selectedDebitNotes.length))}
                    >
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
