import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
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
import InputError from '@/components/ui/input-error';
import { Trash2 } from 'lucide-react';
import { CreateVendorPaymentFormData, CreateVendorPaymentProps, PurchaseInvoice, DebitNote } from './types';
import { formatCurrency } from '@/utils/helpers';

export default function Create({ vendors, bankAccounts, onSuccess }: CreateVendorPaymentProps) {
    const { t } = useTranslation();
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

    const mobileMoneyProviders = [
        { value: 'mpesa', label: 'M-Pesa' },
        { value: 'emola', label: 'e-Mola' },
        { value: 'mkesh', label: 'mKesh' }
    ] as const;

    const { data, setData, post, processing, errors } = useForm<CreateVendorPaymentFormData>({
        payment_date: new Date().toISOString().split('T')[0],
        vendor_id: '',
        bank_account_id: '',
        payment_method: 'bank_transfer',
        mobile_money_provider: '',
        mobile_money_number: '',
        reference_number: '',
        payment_amount: '',
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
        if (data.vendor_id) {
            fetchOutstandingInvoices(data.vendor_id);
        } else {
            setOutstandingInvoices([]);
            setAvailableDebitNotes([]);
        }
        // Clear selections when vendor changes
        setSelectedAllocations([]);
        setSelectedDebitNotes([]);
        setData('payment_amount', '');
    }, [data.vendor_id]);

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
                        <Label htmlFor="bank_account_id" required>{t('Bank Account')}</Label>
                        <Select value={data.bank_account_id} onValueChange={(value) => setData('bank_account_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Bank Account')} />
                            </SelectTrigger>
                            <SelectContent>
                                {bankAccounts?.map((account) => (
                                    <SelectItem key={account.id} value={account.id.toString()}>
                                        {account.account_name} ({account.account_number})
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

                {data.vendor_id && (
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

                {(selectedAllocations.length > 0 || selectedDebitNotes.length > 0) && (
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
                        label={t('Total Payment Amount')}
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
                        disabled={processing || (!selectedAllocations.length && !selectedDebitNotes.length)}
                    >
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
