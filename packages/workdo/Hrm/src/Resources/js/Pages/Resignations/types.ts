import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Resignation {
    id: number;
    employee_id: any;
    employee?: { id: number; name: string };
    last_working_date: any;
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
}

export interface EditResignationFormData {
    employee_id: any;
    last_working_date: any;
    reason: any;
    description: any;
    document: any;
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