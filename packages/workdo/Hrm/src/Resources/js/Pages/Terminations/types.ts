import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface TerminationType {
    id: number;
    name: string;
}

export interface Termination {
    id: number;
    notice_date?: string;
    termination_date: string;
    reason: string;
    description?: string;
    document?: string;
    employee_id?: number;
    employee?: User;
    termination_type_id?: number;
    terminationType?: TerminationType;
    created_at: string;
}

export interface CreateTerminationFormData {
    notice_date: string;
    termination_date: string;
    reason: string;
    description: string;
    document: string;
    employee_id: string;
    termination_type_id: string;
}

export interface EditTerminationFormData {
    notice_date: string;
    termination_date: string;
    reason: string;
    description: string;
    document: string;
    employee_id: string;
    termination_type_id: string;
}

export interface TerminationFilters {
    name: string;
    employee_id: string;
}

export type PaginatedTerminations = PaginatedData<Termination>;
export type TerminationModalState = ModalState<Termination>;

export interface TerminationsIndexProps {
    terminations: PaginatedTerminations;
    auth: AuthContext;
    users: any[];
    terminationtypes: any[];
    [key: string]: unknown;
}

export interface CreateTerminationProps {
    onSuccess: () => void;
}

export interface EditTerminationProps {
    termination: Termination;
    onSuccess: () => void;
}

export interface TerminationShowProps {
    termination: Termination;
    [key: string]: unknown;
}