import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Resignation {
    id: number;
    employee_id: any;
    employee?: { id: number; name: string };
    last_working_date: any;
    legal_notice_required_days?: number;
    legal_notice_provided_days?: number;
    legal_notice_missing_days?: number;
    legal_notice_compliant?: boolean;
    settlement_base_salary_amount?: number;
    settlement_daily_salary_amount?: number;
    settlement_salary_until_exit_amount?: number;
    settlement_unused_leave_days?: number;
    settlement_unused_leave_amount?: number;
    settlement_other_earnings_amount?: number;
    settlement_other_deductions_amount?: number;
    settlement_apply_indemnity?: boolean;
    settlement_indemnity_days_per_year?: number;
    settlement_indemnity_years?: number;
    settlement_indemnity_amount?: number;
    settlement_gross_amount?: number;
    settlement_total_deductions_amount?: number;
    settlement_net_amount?: number;
    settlement_generated_at?: string;
    reason: any;
    description?: any;
    status: any;
    accepted: string;
    rejected: string;
    document?: any;
    approved_by?: any;
    approved_by?: { id: number; name: string };
    created_at: string;
}

export interface CreateResignationFormData {
    employee_id: any;
    last_working_date: any;
    reason: any;
    description: any;
    document: any;
    settlement_unused_leave_days: string;
    settlement_other_earnings_amount: string;
    settlement_other_deductions_amount: string;
    settlement_apply_indemnity: boolean;
    settlement_indemnity_days_per_year: string;
}

export interface EditResignationFormData {
    employee_id: any;
    last_working_date: any;
    reason: any;
    description: any;
    document: any;
    settlement_unused_leave_days: string;
    settlement_other_earnings_amount: string;
    settlement_other_deductions_amount: string;
    settlement_apply_indemnity: boolean;
    settlement_indemnity_days_per_year: string;
}

export interface ResignationFilters {
    name: string;
    employee_id: string;
}

export type PaginatedResignations = PaginatedData<Resignation>;
export type ResignationModalState = ModalState<Resignation>;

export interface ResignationsIndexProps {
    resignations: PaginatedResignations;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateResignationProps {
    onSuccess: () => void;
}

export interface EditResignationProps {
    resignation: Resignation;
    onSuccess: () => void;
}

export interface ResignationShowProps {
    resignation: Resignation;
    [key: string]: unknown;
}
