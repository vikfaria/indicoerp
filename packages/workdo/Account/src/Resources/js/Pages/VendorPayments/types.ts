import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Vendor {
    id: number;
    name: string;
    email: string;
}

export interface BankAccount {
    id: number;
    account_name: string;
    account_number: string;
    bank_name: string;
}

export interface PurchaseInvoice {
    id: number;
    invoice_number: string;
    invoice_date: string;
    total_amount: number;
    balance_amount: number;
    status: string;
    counterparty_name?: string;
    counterparty_tax_label?: string | null;
    counterparty_tax_number?: string | null;
}

export interface DebitNote {
    id: number;
    debit_note_number: string;
    debit_note_date: string;
    total_amount: number;
    balance_amount: number;
    status: string;
    counterparty_name?: string;
    counterparty_tax_label?: string | null;
    counterparty_tax_number?: string | null;
}

export interface VendorPaymentAllocation {
    id: number;
    invoice_id: number;
    allocated_amount: number;
    invoice: PurchaseInvoice;
}

export interface VendorPayment {
    id: number;
    payment_number: string;
    payment_date: string;
    vendor_id: number;
    bank_account_id: number;
    payment_method: 'bank_transfer' | 'cash' | 'cheque' | 'card' | 'mobile_money' | 'other';
    mobile_money_provider?: 'mpesa' | 'emola' | 'mkesh' | null;
    mobile_money_number?: string | null;
    reference_number?: string;
    payment_amount: number;
    status: 'pending' | 'cleared' | 'cancelled';
    notes?: string;
    vendor: Vendor;
    bank_account: BankAccount;
    allocations: VendorPaymentAllocation[];
    created_at: string;
}

export interface CreateVendorPaymentFormData {
    payment_date: string;
    vendor_id: string;
    bank_account_id: string;
    payment_method: 'bank_transfer' | 'cash' | 'cheque' | 'card' | 'mobile_money' | 'other';
    mobile_money_provider: '' | 'mpesa' | 'emola' | 'mkesh';
    mobile_money_number: string;
    reference_number: string;
    payment_amount: string;
    notes: string;
    allocations: {
        invoice_id: number;
        amount: number;
    }[];
    debit_notes: {
        debit_note_id: number;
        amount: number;
    }[];
}

export interface VendorPaymentFilters {
    vendor_id: string;
    status: string;
    search: string;
}

export type PaginatedVendorPayments = PaginatedData<VendorPayment>;
export type VendorPaymentModalState = ModalState<VendorPayment>;

export interface VendorPaymentsIndexProps {
    payments: PaginatedVendorPayments;
    vendors: Vendor[];
    bankAccounts: BankAccount[];
    filters: VendorPaymentFilters;
    auth: AuthContext;
}

export interface CreateVendorPaymentProps {
    vendors: Vendor[];
    bankAccounts: BankAccount[];
    onSuccess: () => void;
}

export interface VendorPaymentViewProps {
    payment: VendorPayment;
}
