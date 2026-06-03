import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Customer {
    id: number;
    name: string;
    email: string;
    fiscal_residency_status?: 'resident' | 'non_resident';
    fiscal_country?: string | null;
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
    currency_code?: string;
    exchange_rate?: number | null;
    foreign_amount?: number | null;
    amount_mzn?: number | null;
    fx_difference_amount?: number | null;
    is_export_receipt?: boolean;
    receipt_origin_country?: string | null;
    export_reference?: string | null;
    intermediary_bank?: string | null;
    repatriation_status?: 'not_applicable' | 'pending' | 'partial' | 'completed' | null;
    repatriated_amount_mzn?: number | null;
    fx_compliance_reference?: string | null;
    gifim_alert_required?: boolean;
    gifim_alert_category?: 'cash_threshold' | 'electronic_threshold' | null;
    gifim_alert_status?: 'not_required' | 'pending' | 'communicated' | null;
    gifim_reference?: string | null;
    gifim_reported_at?: string | null;
    gifim_reported_by?: number | null;
    gifim_submitted_document?: string | null;
    gifim_justification?: string | null;
    high_value_approval_reference?: string | null;
    approval_required?: boolean;
    approval_status?: 'not_required' | 'pending' | 'approved' | 'rejected' | null;
    approval_risk_flags?: string[] | null;
    approval_requested_at?: string | null;
    approval_reference?: string | null;
    approved_at?: string | null;
    approved_by?: number | null;
    rejection_reason?: string | null;
    rejected_at?: string | null;
    rejected_by?: number | null;
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
    currency_code: string;
    exchange_rate: string;
    foreign_amount: string;
    is_export_receipt: boolean;
    receipt_origin_country: string;
    export_reference: string;
    intermediary_bank: string;
    repatriation_status: 'not_applicable' | 'pending' | 'partial' | 'completed';
    repatriated_amount_mzn: string;
    fx_compliance_reference: string;
    gifim_alert_status: 'not_required' | 'pending' | 'communicated';
    gifim_reference: string;
    gifim_reported_at: string;
    gifim_submitted_document: string;
    gifim_justification: string;
    high_value_approval_reference: string;
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
