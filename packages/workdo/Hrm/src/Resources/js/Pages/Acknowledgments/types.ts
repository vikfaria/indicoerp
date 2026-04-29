import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface HrmDocument {
    id: number;
    name: string;
}

export interface Acknowledgment {
    id: number;
    employee_id: number;
    document_id?: number;
    status: string;
    acknowledgment_note?: string;
    acknowledged_at?: any;
    assigned_by?: number;
    employee?: User;
    document?: HrmDocument;
    assignedBy?: User;
    created_at: string;
}

export interface CreateAcknowledgmentFormData {
    employee_id: string;
    document_id: string;
    status: string;
    acknowledgment_note: string;
    acknowledged_at: any;
    assigned_by: string;
}

export interface EditAcknowledgmentFormData {
    employee_id: string;
    document_id: string;
    status: string;
    acknowledgment_note: string;
    acknowledged_at: any;
    assigned_by: string;
}

export interface AcknowledgmentFilters {
    acknowledgment_note: string;
    employee_id: string;
    document_id: string;
    status: string;
}

export type PaginatedAcknowledgments = PaginatedData<Acknowledgment>;
export type AcknowledgmentModalState = ModalState<Acknowledgment>;

export interface AcknowledgmentsIndexProps {
    acknowledgments: PaginatedAcknowledgments;
    auth: AuthContext;
    users: any[];
    hrmdocuments: any[];
    [key: string]: unknown;
}

export interface CreateAcknowledgmentProps {
    onSuccess: () => void;
}

export interface EditAcknowledgmentProps {
    acknowledgment: Acknowledgment;
    onSuccess: () => void;
}

export interface AcknowledgmentShowProps {
    acknowledgment: Acknowledgment;
    [key: string]: unknown;
}