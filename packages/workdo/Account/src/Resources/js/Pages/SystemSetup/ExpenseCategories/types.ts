import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface ExpenseCategories {
    id: number;
    category_name: any;
    category_code: any;
    gl_account_id: any;
    description?: any;
    is_active: boolean;
    gl_account?: ChartOfAccount;
    created_at: string;
}

export interface ExpenseCategoriesFormData {
    category_name: any;
    category_code: any;
    gl_account_id: any;
    description: any;
    is_active: boolean;
}

export interface CreateExpenseCategoriesProps extends CreateProps {
    chartofaccounts: any[];
}

export interface EditExpenseCategoriesProps extends EditProps<ExpenseCategories> {
    chartofaccounts: any[];
}

export type PaginatedExpenseCategories = PaginatedData<ExpenseCategories>;
export type ExpenseCategoriesModalState = ModalState<ExpenseCategories>;

export interface ExpenseCategoriesIndexProps {
    expensecategories: PaginatedExpenseCategories;
    auth: AuthContext;
    chartofaccounts: any[];
    [key: string]: unknown;
}