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
    offboarding_letter_delivered_at?: string;
    offboarding_assets_returned_at?: string;
    offboarding_access_revoked_at?: string;
    offboarding_final_payment_at?: string;
    offboarding_certificate_issued_at?: string;
    offboarding_inss_notified_at?: string;
    offboarding_migration_notified_at?: string;
    offboarding_archive_completed_at?: string;
    offboarding_completed_at?: string;
    offboarding_notes?: string;
    reason: string;
    description?: string;
    document?: string;
    status?: string;
    employee_id?: number;
    employee?: User;
    termination_type_id?: number;
    terminationType?: TerminationType;
    approved_by?: User;
    created_at: string;
}

export interface CreateTerminationFormData {
    notice_date: string;
    termination_date: string;
    offboarding_letter_delivered_at: string;
    offboarding_assets_returned_at: string;
    offboarding_access_revoked_at: string;
    offboarding_final_payment_at: string;
    offboarding_certificate_issued_at: string;
    offboarding_inss_notified_at: string;
    offboarding_migration_notified_at: string;
    offboarding_archive_completed_at: string;
    offboarding_completed_at: string;
    offboarding_notes: string;
    reason: string;
    description: string;
    document: string;
    employee_id: string;
    termination_type_id: string;
}

export interface EditTerminationFormData {
    notice_date: string;
    termination_date: string;
    offboarding_letter_delivered_at: string;
    offboarding_assets_returned_at: string;
    offboarding_access_revoked_at: string;
    offboarding_final_payment_at: string;
    offboarding_certificate_issued_at: string;
    offboarding_inss_notified_at: string;
    offboarding_migration_notified_at: string;
    offboarding_archive_completed_at: string;
    offboarding_completed_at: string;
    offboarding_notes: string;
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
