import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface WarningType {
    id: number;
    name: string;
}

export interface Warning {
    id: number;
    subject: string;
    severity: string;
    warning_date: string;
    note_of_culpa_issued_at?: string;
    note_of_culpa_delivered_at?: string;
    worker_refused_note_of_culpa?: boolean;
    refusal_witness_one_name?: string;
    refusal_witness_two_name?: string;
    response_deadline_at?: string;
    decision_deadline_at?: string;
    disciplinary_sanction?: string;
    disciplinary_decision_at?: string;
    description?: string;
    document?: string;
    status?: string;
    employee_response?: string;
    is_cancelled?: boolean;
    cancelled_at?: string;
    cancelled_by?: number;
    cancellation_reason?: string;
    employee_id?: number;
    employee?: User;
    warning_by?: number;
    warningBy?: User;
    warning_type_id?: number;
    warningType?: WarningType;
    created_at: string;
}

export interface CreateWarningFormData {
    subject: string;
    severity: string;
    warning_date: string;
    note_of_culpa_issued_at: string;
    note_of_culpa_delivered_at: string;
    worker_refused_note_of_culpa: boolean;
    refusal_witness_one_name: string;
    refusal_witness_two_name: string;
    response_deadline_at: string;
    decision_deadline_at: string;
    disciplinary_sanction: string;
    disciplinary_decision_at: string;
    description: string;
    document: string;
    employee_id: string;
    warning_by: string;
    warning_type_id: string;
}

export interface EditWarningFormData {
    subject: string;
    severity: string;
    warning_date: string;
    note_of_culpa_issued_at: string;
    note_of_culpa_delivered_at: string;
    worker_refused_note_of_culpa: boolean;
    refusal_witness_one_name: string;
    refusal_witness_two_name: string;
    response_deadline_at: string;
    decision_deadline_at: string;
    disciplinary_sanction: string;
    disciplinary_decision_at: string;
    description: string;
    document: string;
    employee_id: string;
    warning_by: string;
    warning_type_id: string;
}

export interface WarningFilters {
    subject: string;
    employee_id: string;
}

export type PaginatedWarnings = PaginatedData<Warning>;
export type WarningModalState = ModalState<Warning>;

export interface WarningsIndexProps {
    warnings: PaginatedWarnings;
    auth: AuthContext;
    users: any[];
    warningtypes: any[];
    [key: string]: unknown;
}

export interface CreateWarningProps {
    onSuccess: () => void;
}

export interface EditWarningProps {
    warning: Warning;
    onSuccess: () => void;
}

export interface WarningShowProps {
    warning: Warning;
    [key: string]: unknown;
}
