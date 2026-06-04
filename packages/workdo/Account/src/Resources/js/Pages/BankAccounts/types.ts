import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface ChartOfAccount {
    id: number;
    name: string;
}

export interface Branch {
    id: number;
    branch_name: string;
}

export interface BankAccount {
    id: number;
    account_number: string;
    account_name: string;
    bank_name: string;
    branch_name?: string;
    branch_id?: number;
    branch?: Branch;
    account_type: string;
    //    payment_gateway?: string;
    opening_balance: number;
    current_balance: number;
    iban?: string;
    swift_code?: string;
    routing_number?: string;
    is_active: boolean;
    is_electronic_money_account?: boolean;
    electronic_money_entity?: string;
    electronic_money_level?: string;
    electronic_money_daily_limit_mzn?: number;
    electronic_money_monthly_limit_mzn?: number;
    electronic_money_limit_exempt_for_enterprise?: boolean;
    electronic_money_account_purpose?: string;
    gl_account_id?: number;
    gl_account?: ChartOfAccount;
    created_at: string;
    [key: string]: any;
}

export interface CreateBankAccountFormData {
    account_number: string;
    account_name: string;
    bank_name: string;
    branch_name: string;
    branch_id: string;
    account_type: string;
    //    payment_gateway: string;
    opening_balance: string;
    current_balance: string;
    iban: string;
    swift_code: string;
    routing_number: string;
    is_active: boolean;
    is_electronic_money_account: boolean;
    electronic_money_entity: string;
    electronic_money_level: string;
    electronic_money_daily_limit_mzn: string;
    electronic_money_monthly_limit_mzn: string;
    electronic_money_limit_exempt_for_enterprise: boolean;
    electronic_money_account_purpose: string;
    gl_account_id: string;
}

export interface EditBankAccountFormData {
    account_number: string;
    account_name: string;
    bank_name: string;
    branch_name: string;
    branch_id: string;
    account_type: string;
    //    payment_gateway: string;
    opening_balance: string;
    current_balance: string;
    iban: string;
    swift_code: string;
    routing_number: string;
    is_active: boolean;
    is_electronic_money_account: boolean;
    electronic_money_entity: string;
    electronic_money_level: string;
    electronic_money_daily_limit_mzn: string;
    electronic_money_monthly_limit_mzn: string;
    electronic_money_limit_exempt_for_enterprise: boolean;
    electronic_money_account_purpose: string;
    gl_account_id: string;
}

export interface BankAccountFilters {
    account_number: string;
    account_name: string;
    bank_name: string;
    account_type: string;
    is_active: string;
}

export type PaginatedBankAccounts = PaginatedData<BankAccount>;
export type BankAccountModalState = ModalState<BankAccount>;

export interface BankAccountsIndexProps {
    bankaccounts: PaginatedBankAccounts;
    auth: AuthContext;
    chartofaccounts: any[];
    branches: Branch[];
    [key: string]: unknown;
}

export interface CreateBankAccountProps {
    onSuccess: () => void;
}

export interface EditBankAccountProps {
    bankaccount: BankAccount;
    onSuccess: () => void;
}

export interface BankAccountShowProps {
    bankaccount: BankAccount;
    [key: string]: unknown;
}
