import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface AccountType {
    id: number;
    name: string;
}

export interface ChartOfAccount {
    id: number;
    name: string;
}

export interface ChartOfAccount {
    id: number;
    account_code: string;
    account_name: string;
    normal_balance: string;
    opening_balance?: number;
    current_balance?: number;
    is_active: boolean;
    description?: string;
    account_type_id?: number;
    account_type?: AccountType;
    parent_account_id?: number;
    parent_account?: ChartOfAccount;
    created_at: string;
}

export interface CreateChartOfAccountFormData {
    account_code: string;
    account_name: string;
    level: string;
    normal_balance: string;
    opening_balance: string;
    current_balance: string;
    is_active: boolean;
    description: string;
    account_type_id: string;
    parent_account_id: string;
}

export interface EditChartOfAccountFormData {
    account_code: string;
    account_name: string;
    normal_balance: string;
    opening_balance: string;
    current_balance: string;
    is_active: boolean;
    description: string;
    account_type_id: string;
    parent_account_id: string;
}

export interface ChartOfAccountFilters {
    account_code: string;
    account_name: string;
    account_type_id: string;
    normal_balance: string;
    is_active: string;
}

export type PaginatedChartOfAccounts = PaginatedData<ChartOfAccount>;
export type ChartOfAccountModalState = ModalState<ChartOfAccount>;

export interface ChartOfAccountsIndexProps {
    chartofaccounts: PaginatedChartOfAccounts;
    auth: AuthContext;
    accounttypes: any[];
    [key: string]: unknown;
}

export interface CreateChartOfAccountProps {
    onSuccess: () => void;
}

export interface EditChartOfAccountProps {
    chartofaccount: ChartOfAccount;
    onSuccess: () => void;
}

export interface ChartOfAccountShowProps {
    chartofaccount: ChartOfAccount;
    [key: string]: unknown;
}