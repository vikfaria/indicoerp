import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Vendor {
    id: number;
    name: string;
    email: string;
    fiscal_residency_status?: 'resident' | 'non_resident';
    fiscal_country?: string | null;
    withholding_tax_applicable?: boolean;
    adt_eligible?: boolean;
    adt_country?: string | null;
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
    currency_code?: string;
    exchange_rate?: number | null;
    foreign_amount?: number | null;
    amount_mzn?: number | null;
    fx_difference_amount?: number | null;
    is_international_payment?: boolean;
    beneficiary_country?: string | null;
    service_type?: string | null;
    withholding_tax_treatment?: 'withheld' | 'exempt' | 'adt_reduced' | null;
    withholding_tax_rate?: number | null;
    withholding_tax_amount?: number | null;
    withholding_exemption_reason?: string | null;
    adt_certificate_reference?: string | null;
    fiscal_compliance_reference?: string | null;
    financial_approval_reference?: string | null;
    fx_authorization_reference?: string | null;
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
    currency_code: string;
    exchange_rate: string;
    foreign_amount: string;
    is_international_payment: boolean;
    beneficiary_country: string;
    service_type: string;
    withholding_tax_treatment: '' | 'withheld' | 'exempt' | 'adt_reduced';
    withholding_tax_rate: string;
    withholding_tax_amount: string;
    withholding_exemption_reason: string;
    adt_certificate_reference: string;
    fiscal_compliance_reference: string;
    financial_approval_reference: string;
    fx_authorization_reference: string;
    contract_reference: string;
    invoice_reference: string;
    bank_settlement_reference: string;
    withholding_receipt_reference: string;
    correspondence_reference: string;
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
