import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Customer {
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

export interface SalesInvoice {
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

export interface CreditNote {
    id: number;
    credit_note_number: string;
    credit_note_date: string;
    total_amount: number;
    balance_amount: number;
    status: string;
    counterparty_name?: string;
    counterparty_tax_label?: string | null;
    counterparty_tax_number?: string | null;
}

export interface CustomerPaymentAllocation {
    id: number;
    invoice_id: number;
    allocated_amount: number;
    invoice: SalesInvoice;
}

export interface CustomerPayment {
    id: number;
    payment_number: string;
    payment_date: string;
    customer_id: number;
    bank_account_id: number;
    payment_method: 'bank_transfer' | 'cash' | 'cheque' | 'card' | 'mobile_money' | 'other';
    mobile_money_provider?: 'mpesa' | 'emola' | 'mkesh' | null;
    mobile_money_number?: string | null;
    reference_number?: string;
    payment_amount: number;
    status: 'pending' | 'cleared' | 'cancelled';
    notes?: string;
    customer: Customer;
    bank_account: BankAccount;
    allocations: CustomerPaymentAllocation[];
    created_at: string;
}

export interface CreateCustomerPaymentFormData {
    payment_date: string;
    customer_id: string;
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
    credit_notes: {
        credit_note_id: number;
        amount: number;
    }[];
}

export interface CustomerPaymentFilters {
    customer_id: string;
    status: string;
    search: string;
}

export type PaginatedCustomerPayments = PaginatedData<CustomerPayment>;
export type CustomerPaymentModalState = ModalState<CustomerPayment>;

export interface CustomerPaymentsIndexProps {
    payments: PaginatedCustomerPayments;
    customers: Customer[];
    bankAccounts: BankAccount[];
    filters: CustomerPaymentFilters;
    auth: AuthContext;
}

export interface CreateCustomerPaymentProps {
    customers: Customer[];
    bankAccounts: BankAccount[];
    onSuccess: () => void;
}

export interface CustomerPaymentViewProps {
    payment: CustomerPayment;
}
