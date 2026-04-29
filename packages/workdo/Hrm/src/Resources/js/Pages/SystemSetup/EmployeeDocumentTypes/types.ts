import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface EmployeeDocumentType {
    id: number;
    document_name: string;
    description?: string;
    is_required: boolean;
    created_at: string;
}

export interface EmployeeDocumentTypeFormData {
    document_name: string;
    description: string;
    is_required: boolean;
}

export interface CreateEmployeeDocumentTypeProps extends CreateProps {
}

export interface EditEmployeeDocumentTypeProps extends EditProps<EmployeeDocumentType> {
}

export type PaginatedEmployeeDocumentTypes = PaginatedData<EmployeeDocumentType>;
export type EmployeeDocumentTypeModalState = ModalState<EmployeeDocumentType>;

export interface EmployeeDocumentTypesIndexProps {
    employeedocumenttypes: PaginatedEmployeeDocumentTypes;
    auth: AuthContext;
    [key: string]: unknown;
}