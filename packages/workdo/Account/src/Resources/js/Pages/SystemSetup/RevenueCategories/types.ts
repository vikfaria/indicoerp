import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface RevenueCategories {
    id: number;
    category_name: any;
    category_code: any;
    gl_account_id: any;
    description?: any;
    is_active: boolean;
    gl_account?: ChartOfAccount;
    created_at: string;
}

export interface RevenueCategoriesFormData {
    category_name: any;
    category_code: any;
    gl_account_id: any;
    description: any;
    is_active: boolean;
}

export interface CreateRevenueCategoriesProps extends CreateProps {
    chartofaccounts: any[];
}

export interface EditRevenueCategoriesProps extends EditProps<RevenueCategories> {
    chartofaccounts: any[];
}

export type PaginatedRevenueCategories = PaginatedData<RevenueCategories>;
export type RevenueCategoriesModalState = ModalState<RevenueCategories>;

export interface RevenueCategoriesIndexProps {
    revenuecategories: PaginatedRevenueCategories;
    auth: AuthContext;
    chartofaccounts: any[];
    [key: string]: unknown;
}