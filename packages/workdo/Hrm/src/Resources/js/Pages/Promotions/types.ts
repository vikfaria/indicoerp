import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Promotion {
    id: number;
    employee_id: any;
    previous_branch_id: any;
    previous_department_id: any;
    previous_designation_id: any;
    current_branch_id: any;
    current_department_id: any;
    current_designation_id: any;
    effective_date: any;
    reason?: any;
    document?: any;
    status: any;
    approved: string;
    rejected: string;
    created_at: string;
}

export interface CreatePromotionFormData {
    employee_id: any;
    previous_branch_id: any;
    previous_department_id: any;
    previous_designation_id: any;
    current_branch_id: any;
    current_department_id: any;
    current_designation_id: any;
    effective_date: any;
    reason: any;
    document: any;
    status: any;
    approved: string;
    rejected: string;
}

export interface EditPromotionFormData {
    employee_id: any;
    previous_branch_id: any;
    previous_department_id: any;
    previous_designation_id: any;
    current_branch_id: any;
    current_department_id: any;
    current_designation_id: any;
    effective_date: any;
    reason: any;
    document: any;
    status: any;
    approved: string;
    rejected: string;
}

export interface PromotionFilters {
    name: string;
    employee_id: string;
}

export type PaginatedPromotions = PaginatedData<Promotion>;
export type PromotionModalState = ModalState<Promotion>;

export interface PromotionsIndexProps {
    promotions: PaginatedPromotions;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreatePromotionProps {
    onSuccess: () => void;
}

export interface EditPromotionProps {
    promotion: Promotion;
    onSuccess: () => void;
}

export interface PromotionShowProps {
    promotion: Promotion;
    [key: string]: unknown;
}