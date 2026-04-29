export interface Revenue {
    id: number;
    revenue_date: string;
    category_id: number;
    bank_account_id: number;
    chart_of_account_id?: number;
    amount: string;
    description: string;
    reference_number: string;
    status: 'draft' | 'approved' | 'posted';
    approved_by: number | null;
    creator_id: number;
    created_by: number;
    created_at: string;
    updated_at: string;
    category?: {
        id: number;
        category_name: string;
    };
    bank_account?: {
        id: number;
        account_name: string;
    };
    chart_of_account?: {
        id: number;
        account_code: string;
        account_name: string;
    };
    approved_by_user?: {
        id: number;
        name: string;
    };
    creator?: {
        id: number;
        name: string;
    };
}

export interface Category {
    id: number;
    category_name: string;
}

export interface BankAccount {
    id: number;
    account_name: string;
}

export interface ChartOfAccount {
    id: number;
    account_code: string;
    account_name: string;
}

export interface RevenueIndexProps {
    revenues: Revenue[];
    auth: any;
}

export interface CreateRevenueProps {
    categories: Category[];
    bankAccounts: BankAccount[];
    chartOfAccounts: ChartOfAccount[];
}

export interface EditRevenueProps {
    revenue: Revenue;
    categories: Category[];
    bankAccounts: BankAccount[];
    chartOfAccounts: ChartOfAccount[];
}

export interface ShowRevenueProps {
    revenue: Revenue;
}

export interface CreateRevenueFormData {
    revenue_date: string;
    category_id: string;
    bank_account_id: string;
    chart_of_account_id: string;
    amount: string;
    description: string;
    reference_number: string;
    status: 'draft' | 'approved' | 'posted';
}

export interface UpdateRevenueFormData extends CreateRevenueFormData {}